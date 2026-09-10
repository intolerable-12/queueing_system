<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Select Priority</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            overflow-x: hidden;
        }

        body {
            background-color: #ffedf5;
        }

        .header-bar {
            background: linear-gradient(90deg, #ff4fa0, #ff82c4);
            padding: 22px 20px 22px 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 10px rgba(255, 60, 140, 0.35);
        }

        .header-bar .circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #fff;
            background-image: url('/images/LCCDO.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            margin-right: 20px;
            border: 3px solid #ffbad6;
        }

        .header-bar h5 {
            color: #fff;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .main-wrapper {
            background: white;
            padding: 40px 60px;
            margin: 25px;
            border-radius: 20px;
            border: 3px solid #ffbad6;
            box-shadow: 0 4px 12px rgba(255, 120, 170, 0.3);
            min-height: calc(100vh - 160px);
            overflow: hidden;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo-section img {
            width: 400px;
            max-width: 90vw;
            height: auto;
        }

        .instruction-text {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 40px;
            color: #c2185b;
        }

        .button-section {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .button-column {
            flex: 1 1 300px;
            min-width: 260px;
            max-width: 100%;
            display: flex;
            flex-direction: column;
        }

        .priority-btn {
            width: 100%;
            padding: 25px;
            font-size: 28px;
            font-weight: bold;
            border-radius: 15px;
            background: #ff78b6;
            color: white;
            border: none;
            box-shadow: 0 4px 10px rgba(255, 60, 140, 0.35);
            transition: all 0.3s ease;
            margin-top: auto;
        }

        .priority-btn:hover {
            background: #ff4fa0;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 60, 140, 0.5);
        }

        ul {
            color: #8c0f45;
            font-size: 22px;
            padding-left: 20px;
            margin-bottom: 0;
        }

        ul li {
            margin-bottom: 8px;
            font-size: 22px;
        }

        /* Modal Styles */
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            border-radius: 20px;
            border: 3px solid #ffbad6;
            box-shadow: 0 4px 12px rgba(255, 120, 170, 0.3);
        }

        .modal-header {
            background: linear-gradient(90deg, #ff4fa0, #ff82c4);
            color: white;
            border-radius: 17px 17px 0 0;
            border-bottom: none;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .modal-body {
            padding: 30px;
            background-color: #ffedf5;
        }

        .confirm-text {
            font-size: 18px;
            color: #8c0f45;
            margin-bottom: 15px;
        }

        .confirm-text strong {
            color: #c2185b;
        }

        .confirm-btn {
            width: 100%;
            padding: 15px;
            font-size: 24px;
            font-weight: bold;
            border-radius: 15px;
            background: #ff78b6;
            color: white;
            border: none;
            box-shadow: 0 4px 10px rgba(255, 60, 140, 0.35);
            transition: all 0.3s ease;
        }

        .confirm-btn:hover {
            background: #ff4fa0;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 60, 140, 0.5);
        }

        .cancel-btn {
            width: 100%;
            padding: 15px;
            font-size: 24px;
            font-weight: bold;
            border-radius: 15px;
            background: #e0e0e0;
            color: #666;
            border: none;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .cancel-btn:hover {
            background: #c0c0c0;
            transform: translateY(-2px);
        }

        /* Responsive Styles */
        @media (max-width: 1200px) {
            .main-wrapper {
                padding: 30px 10px;
                margin: 10px;
            }

            .logo-section img {
                width: 300px;
            }
        }

        @media (max-width: 900px) {
            .button-section {
                flex-direction: column;
                gap: 30px;
            }

            .button-column {
                min-width: 0;
            }

            .main-wrapper {
                padding: 20px 5px;
            }

            .logo-section img {
                width: 220px;
            }

            .instruction-text {
                font-size: 20px;
            }

            ul,
            ul li {
                font-size: 18px;
            }
        }

        @media (max-width: 600px) {
            .header-bar {
                flex-direction: column;
                align-items: flex-start;
                padding: 10px 10px;
            }

            .header-bar .circle {
                margin-bottom: 10px;
                margin-right: 0;
            }

            .header-bar h5 {
                font-size: 1.1rem;
            }

            .main-wrapper {
                padding: 10px 2px;
                margin: 2px;
                border-radius: 10px;
            }

            .logo-section img {
                width: 120px;
            }

            .priority-btn {
                font-size: 18px;
                padding: 12px;
            }

            .instruction-text {
                font-size: 16px;
            }

            ul,
            ul li {
                font-size: 15px;
            }

            .confirm-btn,
            .cancel-btn {
                font-size: 16px;
                padding: 10px;
            }

            .modal-body {
                padding: 10px;
            }
        }
    </style>
</head>

<body>

    <!-- TOP HEADER -->
    <div class="header-bar">
        <div class="circle"></div>
        <h5 class="m-0 fw-bold">LOURDES COLLEGE, INC.</h5>
        <div class="ms-auto">
            <a href="{{ route('kiosk.index') }}" class="btn btn-light fw-bold" style="border:2px solid #ffbad6;">
                ← Back
            </a>
        </div>
    </div>

    <div class="main-wrapper">

        @error('printer')
            <div class="alert alert-danger fw-bold" role="alert"
                style="border:2px solid #ffbad6; box-shadow:0 3px 6px rgba(255,60,140,0.25)">
                <div class="d-flex align-items-center">
                    <span class="me-2">⚠️</span>
                    <span>{{ $message }}</span>
                </div>
            </div>
        @enderror

        <!-- LOGO -->
        <div class="logo-section">
            <img src="/images/Lourdes.png">
        </div>

        <div class="instruction-text">
            PLEASE CLICK THE BUTTON IF IT APPLIES TO YOU.
        </div>

        <!-- THREE COLUMN LAYOUT -->
        <div class="button-section">
            <!-- COLUMN 1: PWD/SENIOR/PREGNANT -->
            <div class="button-column">
                <ul>
                    <p>Cashier transaction for:</p>
                    <li>Persons with disabilities</li>
                    <li>60+ years old</li>
                    <li>Pregnant Woman</li>
                    <li>Legal Guardian/Parent of a student</li>

                </ul>
                <button type="button" class="btn priority-btn"
                    onclick="showConfirmModal('{{ $service }}', 'pwd_senior_pregnant', 'PWD/SENIOR/PREGNANT')">
                    PRIORITY
                </button>
            </div>
            <!-- COLUMN 2: STUDENT -->
            <div class="button-column">
                <ul>
                    <p>Cashier transaction for:</p>
                    <li>Senior High</li>
                    <li>College</li>
                    <li>Graduate School</li>

                </ul>
                <button type="button" class="btn priority-btn"
                    onclick="showConfirmModal('{{ $service }}', 'student', 'STUDENT')">
                    STUDENT
                </button>
            </div>
            @if($service === 'cashier')
            <!-- COLUMN 3: CLEARANCE -->
            <div class="button-column">
                <ul>
                    <li>Student Clearance</li>

                </ul>
                <button type="button" class="btn priority-btn"
                    onclick="showConfirmModal('{{ $service }}', 'clearance', 'CLEARANCE')">
                    CLEARANCE
                </button>
            </div>
            @endif
        </div>
        <div style="height:50px"></div>

        <div class="d-flex justify-content-end mt-4">
            <a href="{{ route('kiosk.index') }}" class="btn btn-light fw-bold" style="border:2px solid #ffbad6;">
                ← Back
            </a>
        </div>


    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="confirmModalLabel">Confirm Selection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="confirm-text">Service: <strong id="modalService"></strong></p>
                    <p class="confirm-text">Priority: <strong id="modalPriority"></strong></p>
                    <form method="POST" action="{{ route('kiosk.issue') }}" id="confirmForm">
                        @csrf
                        <input type="hidden" name="service" id="hiddenService">
                        <input type="hidden" name="program" id="hiddenProgram">
                        <input type="hidden" name="priority" id="hiddenPriority">
                        <button type="submit" class="btn confirm-btn">Generate Code</button>
                        <button type="button" class="btn cancel-btn" data-bs-dismiss="modal">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const selectedProgram = @json($program ?? null);

        function showConfirmModal(service, priority, priorityLabel) {
            // Set modal content
            document.getElementById('modalService').textContent = service.charAt(0).toUpperCase() + service.slice(1);
            document.getElementById('modalPriority').textContent = priorityLabel;

            // Set hidden form fields
            document.getElementById('hiddenService').value = service;
            document.getElementById('hiddenProgram').value = selectedProgram || '';
            document.getElementById('hiddenPriority').value = priority;

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
            modal.show();
        }
    </script>

</body>

</html>