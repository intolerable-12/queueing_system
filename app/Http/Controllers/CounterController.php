<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\QueueTicket;
use App\Models\QueueCutoff;
use App\Events\QueueCutoffUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Events\TicketUpdated;
use Illuminate\Support\Facades\Auth;

class CounterController extends Controller
{
    protected function withServiceLock(string $serviceType, callable $callback)
    {
        try {
            return Cache::lock('queue_turn_lock_' . $serviceType, 10)
                ->block(5, fn() => $callback());
        } catch (\Throwable $e) {
            return $callback();
        }
    }

    protected function todayTickets()
    {
        return QueueTicket::whereDate('created_at', today());
    }

    protected function scopeTicketsForCounter($query, Counter $counter)
    {
        if ($counter->type === 'registrar') {
            // Show registrar tickets that are either:
            // 1. Not assigned to a specific counter (designated_counter_id IS NULL) - any registrar can service
            // 2. Assigned to this specific counter (designated_counter_id = $counter->id) - only this counter
            $query->where(function ($q) use ($counter) {
                $q->whereNull('designated_counter_id')
                    ->orWhere('designated_counter_id', $counter->id);
            });
        }

        return $query;
    }

    protected function isTicketAllowedForCounter(Counter $counter, QueueTicket $ticket): bool
    {
        if ($counter->type !== 'registrar') {
            return true;
        }

        // Allow registrar to handle tickets that are either unassigned or assigned to this counter
        return $ticket->designated_counter_id === null || (int) $ticket->designated_counter_id === (int) $counter->id;
    }

    protected function getLastCalledTicket(string $serviceType, Counter $counter): ?QueueTicket
    {
        $query = $this->todayTickets()->where('service_type', $serviceType)
            ->whereIn('status', ['serving', 'done', 'on_hold'])
            ->where('called_times', '>', 0)
            ->latest('updated_at');

        return $this->scopeTicketsForCounter($query, $counter)->first();
    }

    protected function getAlternatingStartBucket(string $serviceType, Counter $counter, ?QueueTicket $nextStudent, ?QueueTicket $nextPriority): string
    {
        if (!$nextStudent) {
            return 'priority';
        }

        if (!$nextPriority) {
            return 'student';
        }

        $lastCalled = $this->getLastCalledTicket($serviceType, $counter);

        if (!$lastCalled) {
            return $nextStudent->created_at->lte($nextPriority->created_at) ? 'student' : 'priority';
        }

        return $lastCalled->priority === 'student' ? 'priority' : 'student';
    }

    protected function getNextPendingTicketAlternating(Counter $counter): ?QueueTicket
    {
        $serviceType = $counter->type;

        $studentQuery = $this->todayTickets()->where('service_type', $serviceType)
            ->where('status', 'pending')
            ->where('priority', 'student')
            ->orderBy('created_at');

        $nextStudent = $this->scopeTicketsForCounter($studentQuery, $counter)->first();

        $priorityQuery = $this->todayTickets()->where('service_type', $serviceType)
            ->where('status', 'pending')
            ->where('priority', '!=', 'student')
            ->orderBy('created_at');

        $nextPriority = $this->scopeTicketsForCounter($priorityQuery, $counter)->first();

        if (!$nextStudent && !$nextPriority) {
            return null;
        }

        if (!$nextStudent) {
            return $nextPriority;
        }

        if (!$nextPriority) {
            return $nextStudent;
        }

        $startBucket = $this->getAlternatingStartBucket($serviceType, $counter, $nextStudent, $nextPriority);

        return $startBucket === 'student' ? $nextStudent : $nextPriority;
    }

    protected function getPendingQueueAlternating(Counter $counter)
    {
        $serviceType = $counter->type;

        $studentQuery = $this->todayTickets()->where('service_type', $serviceType)
            ->where('status', 'pending')
            ->where('priority', 'student')
            ->orderBy('created_at');

        $studentQueue = $this->scopeTicketsForCounter($studentQuery, $counter)->get();

        $priorityQuery = $this->todayTickets()->where('service_type', $serviceType)
            ->where('status', 'pending')
            ->where('priority', '!=', 'student')
            ->orderBy('created_at');

        $priorityQueue = $this->scopeTicketsForCounter($priorityQuery, $counter)->get();

        if ($studentQueue->isEmpty() && $priorityQueue->isEmpty()) {
            return collect();
        }

        if ($studentQueue->isEmpty()) {
            return $priorityQueue;
        }

        if ($priorityQueue->isEmpty()) {
            return $studentQueue;
        }

        $startBucket = $this->getAlternatingStartBucket($serviceType, $counter, $studentQueue->first(), $priorityQueue->first());
        $result = collect();
        $turn = $startBucket;

        while ($studentQueue->isNotEmpty() || $priorityQueue->isNotEmpty()) {
            if ($turn === 'student') {
                if ($studentQueue->isNotEmpty()) {
                    $result->push($studentQueue->shift());
                    $turn = 'priority';
                } elseif ($priorityQueue->isNotEmpty()) {
                    $result->push($priorityQueue->shift());
                }
            } else {
                if ($priorityQueue->isNotEmpty()) {
                    $result->push($priorityQueue->shift());
                    $turn = 'student';
                } elseif ($studentQueue->isNotEmpty()) {
                    $result->push($studentQueue->shift());
                }
            }
        }

        return $result;
    }

    public function select()
    {
        $userRole = Auth::user()->role;

        // Only show counters matching user's role
        $counters = Counter::where('type', $userRole)
            ->orderBy('name')
            ->get();

        return view('operator.select', compact('counters', 'userRole'));
    }

    public function claim(Request $request)
    {
        $validated = $request->validate([
            'counter_id' => 'required|exists:counters,id',
        ]);

        $counter = Counter::findOrFail($validated['counter_id']);

        // Check if counter type matches user's role
        if ($counter->type !== Auth::user()->role) {
            return back()->withErrors(['counter' => 'You cannot claim this counter type.']);
        }

        if ($counter->claimed) {
            return back()->withErrors(['counter' => 'Counter already in use.']);
        }

        $counter->claimed = true;
        $counter->save();
        return redirect()->route('counter.show', $counter);
    }

    public function release(Request $request)
    {
        $user = Auth::user();

        if ($user && $user->counter_id) {
            $counter = $user->counter;
            if ($counter) {
                $counter->claimed = false;
                $counter->save();
            }
        }

        return redirect()->route('login');
    }

    public function show(Counter $counter)
    {
        // Verify user has access to this counter
        $user = Auth::user();
        if (!$user || $user->counter_id !== $counter->id) {
            abort(403, 'Unauthorized access to this counter.');
        }

        $queue = $this->getPendingQueueAlternating($counter);

        $onHoldQuery = $this->todayTickets()->where('service_type', $counter->type)
            ->where(function ($query) {
                $query->where('status', 'on_hold')
                    ->orWhere(function ($q) {
                        $q->where('status', 'serving')
                            ->whereNotNull('hold_count')
                            ->where('hold_count', '>', 0);
                    });
            })
            ->orderBy('updated_at', 'asc');

        $onHold = $this->scopeTicketsForCounter($onHoldQuery, $counter)->get();

        $nowServing = $this->todayTickets()->where('counter_id', $counter->id)
            ->where('status', 'serving')
            ->latest()
            ->first();

        return view('operator.counter', compact('counter', 'queue', 'onHold', 'nowServing'));
    }

    public function next(Counter $counter)
    {
        // Server-side rate limiting: prevent rapid clicks (10 second cooldown)
        $lastNextTime = session('last_next_time_' . $counter->id);
        $now = now()->timestamp;

        if ($lastNextTime && ($now - $lastNextTime) < 10) {
            return redirect()->route('counter.show', $counter)->withErrors([
                'rate_limit' => 'Please wait before calling the next ticket.'
            ]);
        }

        // Update last action time
        session(['last_next_time_' . $counter->id => $now]);

        $currentTicket = null;
        //global alternation consistency - if multiple counters of same type call next at the same time, they will still alternate between student and priority based on the last called ticket of that service type, not based on their own last called ticket
        //serialize selection/assignment per service to keep global alternation consistent
        $this->withServiceLock($counter->type, function () use ($counter, &$nextTicket, &$currentTicket) {
            DB::transaction(function () use ($counter, &$nextTicket, &$currentTicket) {
                // Mark currently serving ticket as done 
                $currentTicket = QueueTicket::where('counter_id', $counter->id)
                    ->where('status', 'serving')
                    ->first();

                if ($currentTicket) {
                    $currentTicket->status = 'done';
                    $currentTicket->counter_id = null;
                    $currentTicket->save();
                }

                // Get next pending ticket using alternating student/priority strategy
                $nextTicket = $this->getNextPendingTicketAlternating($counter);

                if ($nextTicket) {
                    // this ensures same ticket not served by another counter
                    if ($nextTicket->status === 'serving' && $nextTicket->counter_id !== $counter->id) {
                        throw new \Exception('Ticket already serving in another counter.');
                    }

                    $nextTicket->status = 'serving';
                    $nextTicket->counter_id = $counter->id;
                    $nextTicket->called_times = ($nextTicket->called_times ?? 0) + 1;
                    $nextTicket->save();
                }
            });
        });

        // Broadcast events after transaction completes
        if ($currentTicket) {
            event(new TicketUpdated('done', $currentTicket));
        }

        if (!$nextTicket) {
            // No more tickets - just return without serving anything
            return redirect()->route('counter.show', $counter)->with('status', 'No pending ticket.');
        }

        event(new TicketUpdated('serving', $nextTicket));

        // autoremove oldest on-hold after every 3 Next presses base on total calls
        static $nextPressCount = 0;
        $nextPressCount++;

        $removed = false;
        if ($nextPressCount % 3 === 0) {
            $oldestHoldQuery = $this->todayTickets()->where('service_type', $counter->type)
                ->where('status', 'on_hold')
                ->orderBy('updated_at', 'asc');

            $oldestHold = $this->scopeTicketsForCounter($oldestHoldQuery, $counter)->first();
            if ($oldestHold) {
                $oldestHold->status = 'done';
                $oldestHold->save();
                event(new TicketUpdated('done', $oldestHold));
                $removed = true;
            }
        }

        return redirect()->route('counter.show', $counter)->with('status', $removed ? 'Oldest on-hold removed.' : 'Serving next.');
    }

    public function hold(Counter $counter, QueueTicket $ticket)
    {
        // Server-side prevent rapid clicks (10 second cooldown)
        $lastHoldTime = session('last_hold_time_' . $counter->id);
        $now = now()->timestamp;

        if ($lastHoldTime && ($now - $lastHoldTime) < 10) {
            return redirect()->route('counter.show', $counter)->withErrors([
                'rate_limit' => 'Please wait before putting a ticket on hold.'
            ]);
        }

        //update last action time
        session(['last_hold_time_' . $counter->id => $now]);

        if ($ticket->status === 'serving' && $ticket->counter_id === $counter->id && $ticket->created_at->isToday() && $this->isTicketAllowedForCounter($counter, $ticket)) {
            $servedAfterHold = null;

            // Serialize selection/assignment per service 
            $this->withServiceLock($counter->type, function () use ($counter, $ticket, &$servedAfterHold) {
                DB::transaction(function () use ($counter, $ticket, &$servedAfterHold) {
                    // Mark current ticket as on-hold
                    $ticket->status = 'on_hold';
                    $ticket->hold_count = ($ticket->hold_count ?? 0) + 1;
                    $ticket->counter_id = null; // Release from this counter
                    $ticket->save();

                    //automatically serve the next pending ticket using alternating strategy
                    $nextTicket = $this->getNextPendingTicketAlternating($counter);

                    if ($nextTicket) {
                        $nextTicket->status = 'serving';
                        $nextTicket->counter_id = $counter->id;
                        $nextTicket->called_times = ($nextTicket->called_times ?? 0) + 1;
                        $nextTicket->save();
                        $servedAfterHold = $nextTicket;
                    }
                });
            });

            event(new TicketUpdated('on_hold', $ticket));

            if ($servedAfterHold) {
                event(new TicketUpdated('serving', $servedAfterHold));
            }
        }
        return redirect()->route('counter.show', $counter);
    }

    public function callAgain(Counter $counter, QueueTicket $ticket)
    {
        if (in_array($ticket->status, ['on_hold', 'serving'], true) && $ticket->service_type === $counter->type && $ticket->created_at->isToday() && $this->isTicketAllowedForCounter($counter, $ticket)) {
            //use transaction mysql function to handle both the current serving ticket and the called ticket
            DB::transaction(function () use ($counter, $ticket) {
                // una kay, handle any currently serving ticket at this counter
                $currentlyServing = QueueTicket::where('counter_id', $counter->id)
                    ->where('status', 'serving')
                    ->where('id', '!=', $ticket->id)
                    ->first();

                if ($currentlyServing) {
                    //put the currently serving ticket back to pending
                    $currentlyServing->status = 'pending';
                    $currentlyServing->counter_id = null;
                    $currentlyServing->save();
                }

                //now serve the called ticket
                $ticket->status = 'serving';
                $ticket->counter_id = $counter->id;
                $ticket->called_times = ($ticket->called_times ?? 0) + 1;
                $ticket->save();
            });

            event(new TicketUpdated('serving', $ticket));
        }
        return redirect()->route('counter.show', $counter);
    }

    // remove hold from hold list
    public function removeHold(Counter $counter, QueueTicket $ticket)
    {
        if (in_array($ticket->status, ['on_hold', 'serving'], true) && $ticket->service_type === $counter->type && $ticket->created_at->isToday() && $this->isTicketAllowedForCounter($counter, $ticket)) {
            $ticket->status = 'done';
            $ticket->save();
            event(new TicketUpdated('done', $ticket));
        }
        return redirect()->route('counter.show', $counter);
    }


    public function cutoff()
    {
        QueueCutoff::updateOrCreate(

            [
                'cutoff_date' => today()
            ],

            [

                'is_closed' => true,

                'closed_by' => auth()->id(),

                'closed_at' => now()

            ]

        );

        event(new QueueCutoffUpdated(true));

        return back()->with('success', 'Queue has been cut off.');
    }

    public function reopen()
    {
        QueueCutoff::updateOrCreate(

            [
                'cutoff_date' => today()
            ],

            [

                'is_closed' => false,

                'closed_by' => null,

                'closed_at' => null

            ]

        );

        event(new QueueCutoffUpdated(false));

        return back()->with('success', 'Queue reopened.');
    }



}
