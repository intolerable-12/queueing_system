<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Kiosk</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

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
            min-height: 100vh;
            width: 100vw;
        }

        /* HEADER */
        .header-bar {
            background: linear-gradient(90deg, #ff4fa0, #ff82c4);
            padding: 22px 20px 22px 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 10px rgba(255, 60, 140, 0.35);
            flex-shrink: 0;
        }

        .header-bar .circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #fff url('/images/LCCDO.png') center / cover no-repeat;
            margin-right: 20px;
            border: 3px solid #ffbad6;
        }

        .header-bar h5 {
            color: #fff;
        }

        /* MAIN WRAPPER */
        .main-wrapper {
            background: white;
            padding: 40px 60px;
            margin: 0;
            border-radius: 20px;
            border: 3px solid #ffbad6;
            box-shadow: 0 4px 12px rgba(255, 120, 170, 0.3);
            min-height: calc(100vh - 80px);
            width: 100vw;
            max-width: 100vw;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .main-wrapper .row {
            flex: 1;
            display: flex;
            align-items: stretch;
        }

        /* LEFT IMAGE */
        .left-image-box {
            background: #ffe6f3;
            border-radius: 15px;
            border: 2px solid #ffc1d9;
            box-shadow: 0 3px 6px rgba(255, 150, 180, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            overflow: hidden;


        }

        .left-image-box img {
            max-width: 70%;
            max-height: 100%;
            object-fit: contain;


        }

        /* RIGHT CONTENT */
        .content-column {
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
            /* prevents layout break */
        }

        .content-column>* {
            flex-shrink: 0;
        }

        /* BUTTONS */
        .service-btn {
            width: 100%;
            padding: 18px;
            font-size: clamp(20px, 2.5vw, 30px);
            font-weight: bold;
            border-radius: 15px;
            background: #ff78b6;
            color: white;
            border: none;
            box-shadow: 0 4px 10px rgba(255, 60, 140, 0.35);
            transition: all 0.3s ease;
        }

        .service-btn:hover {
            background: #ff4fa0;
            transform: translateY(-2px);
        }

        /* TEXT */
        .instruction-text {
            font-size: clamp(20px, 1.6vw, 20px);
            font-weight: bold;
            text-align: center;
            color: #c2185b;
            margin-bottom: 10px;
        }

        ul,
        p {
            color: #8c0f45;
            font-size: clamp(13px, 1.2vw, 15px);
            margin-bottom: 8px;
        }

        ul li {
            margin-bottom: 4px;
            font-size: 30px;
        }

        .divider {
            border-top: 2px solid #ffbad6;
            margin: 12px 0;
        }

        /* MOBILE SAFETY */
        @media (max-width: 768px) {
            .main-wrapper {
                padding: 10px 2px;
                margin: 2px;
                border-radius: 10px;
            }

            .row {
                flex-direction: column;
            }

            .left-image-box {
                max-height: 35vh;
            }
        }
    </style>
</head>

<body>

    <!-- TOP HEADER -->
    <div class="header-bar">
        <div class="circle"></div>
        <h5 class="m-0 fw-bold">LOURDES COLLEGE, INC.</h5>

    </div>

    <div class="main-wrapper mx-auto">

        <div class="row">

            <!-- LEFT IMAGE -->
            <div class="col-md-5">
                <div class="left-image-box">
                    <img src="/images/louna.gif" alt="Staff" class="img-fluid">
                </div>
            </div>

            <!-- RIGHT CONTENT -->
            <div class="col-md-7 ps-4 content-column">

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

                <div class="text-center mb-1" style="user-select: none; cursor: default;">
                    <img src="/images/Lourdes.png" width="280">
                </div>

                <div class="instruction-text">
                    PLEASE CLICK THE BUTTON OF THE RESPECTIVE PERSONNEL YOU NEED.
                </div>

                <!-- FIRST BULLETS -->
                <ul>
                    <li>Payments</li>
                    <li>Statement of Accounts</li>
                    <li>Examination Permit</li>
                    <li>Purchase of P.E. Uniforms</li>
                </ul>
                <!-- CASHIER BUTTON -->
                <form method="POST" action="{{ route('kiosk.service') }}">
                    @csrf
                    <button id="cashierBtn" name="service_type" value="cashier" class="btn service-btn mt-3" {{ $closed ? 'disabled' : '' }}>
                        {{ $closed ? 'QUEUE IS CLOSED FOR TODAY' : 'CASHIER' }}
                    </button>
                </form>

                <div class="divider"></div>

                <!-- SECOND BULLETS -->
                <p style="font-size: 30px">Request of Documents such as:</p>
                <ul>

                    <li>Transcript of Records</li>
                    <li>Honorable Dismissal</li>
                    <li>Certifications</li>
                    <li>Authentication</li>

                </ul>

                <!-- REGISTRAR BUTTON -->
                <form method="POST" action="{{ route('kiosk.service') }}">
                    @csrf
                    <button id="registrarBtn" name="service_type" value="registrar" class="btn service-btn mt-3" {{ $closed ? 'disabled' : '' }}>
                        {{ $closed ? 'QUEUE IS CLOSED FOR TODAY' : 'REGISTRAR' }}
                    </button>
                </form>

            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.Echo) {
                console.error('Echo is not available on the kiosk page.');
                return;
            }

            const cashier = document.getElementById('cashierBtn');

            const registrar = document.getElementById('registrarBtn');

            const updateServiceButtons = (closed) => {
                const closedLabel = 'QUEUE IS CLOSED FOR TODAY';

                cashier.disabled = closed;
                registrar.disabled = closed;
                cashier.textContent = closed ? closedLabel : 'CASHIER';
                registrar.textContent = closed ? closedLabel : 'REGISTRAR';
            };

            window.Echo.channel('queue.cutoff')

                .listen('.cutoff.updated', (e) => {
                    updateServiceButtons(!!e.closed);
                });

        });




        // // Sync left avatar box height to right content (up to registrar button)
        // function syncAvatarHeight() {
        //     try {
        //         const content = document.querySelector('.content-column');
        //         const leftBox = document.querySelector('.left-image-box');
        //         if (!content || !leftBox) return;
        //         const h = content.offsetHeight;
        //         leftBox.style.height = h + 'px';
        //         const img = leftBox.querySelector('img');
        //         if (img) {
        //             img.style.height = '100%';
        //             img.style.width = 'auto';
        //             img.style.objectFit = 'contain';
        //         }
        //     } catch (e) {
        //         console.error(e);
        //     }
        // }

        // document.addEventListener('DOMContentLoaded', () => {
        //     syncAvatarHeight();
        //     window.addEventListener('resize', syncAvatarHeight);
        // });
    </script>

</body>

</html>
