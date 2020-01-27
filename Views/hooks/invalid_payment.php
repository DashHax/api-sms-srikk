<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style>
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        body {
            width: 100vw;
            height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;
        }

        .content {
            width: 500px;
            max-width: 80vw;

            height: fit-content;

            display: flex;
            flex-direction: column;
            justify-content: center;

            background-color: white;
            box-shadow: 0 0 0.75em #ccc;
            padding: 1em;
        }

        .content .logo {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .content .logo img {
            width: 100px;
        }

        .content .body {
            margin-top: 1em;
        }

        .content .body .title {
            text-align: center;
        }

        .content .body .msg {
            margin-top: 1em;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-weight: 400;
        }

        @media screen and (min-width: 768px) {
        }
    </style>
    <title>Payment Gateway</title>
</head>

<body>
    <div class="content">
        <div class="logo">
            <img src="<?= $logo ?>" alt="logo">
            <div class="text">
                <h2>SISTEM PENGURUSAN PELAJAR</h2>
                <h3>Sekolah Rendah Islam Kota Kinabalu</h3>
            </div>
        </div>
        <div class="body">
            <h1 class="title">
                Server Message
            </h1>
            <?php
                if (isset($exception)) {
            ?>
            <p>
                There is an error on the server side: <?=$exception?>
            </p>
            <?php
                }
            ?>
            <p class="msg">
                It seems that there is something wrong with the payment request that were sent.
            </p>
            <p class="msg">
                Therefore, we have to stop it from causing havoc to our server.
            </p>
            <p class="msg">
                If you think this is a mistake, please contact the webmaster, or report this to Sekolah Rendah Islam, Kota Kinabalu.
            </p>
        </div>
    </div>
</body>

</html>