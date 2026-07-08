<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema en Mantenimiento</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .box {
            background: #fff;
            border-radius: 16px;
            padding: 3rem 2.5rem;
            text-align: center;
            max-width: 460px;
            width: 90%;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
        }
        .icon-wrap {
            width: 80px;
            height: 80px;
            background: #fff3cd;
            border: 3px solid #ffc107;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: #856404;
        }
        h1 {
            color: #1a1a2e;
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        p {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 0.4rem;
            font-size: 0.95rem;
        }
        .tag {
            display: inline-block;
            background: #f8d7da;
            color: #842029;
            padding: 0.4rem 1.2rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 1.5rem;
            border: 1px solid #f5c2c7;
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon-wrap">&#9888;</div>
        <h1>Sistema en Mantenimiento</h1>
        <p>El sistema se encuentra temporalmente desactivado.</p>
        <p>Por favor contacte al administrador para más información.</p>
        <span class="tag">503 &mdash; Servicio No Disponible</span>
    </div>
</body>
</html>
