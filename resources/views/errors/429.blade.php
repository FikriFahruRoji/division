<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terlalu Banyak Permintaan - 429</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .container {
            background: white;
            border-radius: 16px;
            padding: 48px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .icon {
            width: 80px;
            height: 80px;
            background: #FEE2E2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        .icon svg {
            width: 40px;
            height: 40px;
            color: #DC2626;
        }
        h1 {
            font-size: 24px;
            color: #1F2937;
            margin: 0 0 16px;
        }
        p {
            color: #6B7280;
            margin: 0 0 24px;
            line-height: 1.6;
        }
        .retry-info {
            background: #FEF3C7;
            border: 1px solid #F59E0B;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .retry-info p {
            color: #92400E;
            margin: 0;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            background: #4F46E5;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #4338CA;
        }
        .code {
            font-size: 72px;
            font-weight: 700;
            color: #E5E7EB;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="code">429</div>
        <div class="icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>
        <h1>Terlalu Banyak Permintaan</h1>
        <p>Anda telah mengirim terlalu banyak permintaan dalam waktu singkat. Sistem kami membatasi permintaan untuk melindungi layanan.</p>
        
        @if(isset($retry_after) && $retry_after)
        <div class="retry-info">
            <p>Silakan tunggu <strong>{{ ceil($retry_after / 60) }} menit</strong> sebelum mencoba lagi.</p>
        </div>
        @else
        <div class="retry-info">
            <p>Silakan tunggu beberapa saat sebelum mencoba lagi.</p>
        </div>
        @endif
        
        <a href="{{ url('/') }}" class="btn">Kembali ke Beranda</a>
    </div>
</body>
</html>
