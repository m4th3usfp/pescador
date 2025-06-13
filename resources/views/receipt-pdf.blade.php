<html>
<head>
    <style>
        .container {
            padding: 20px;
            border: 1px solid #000;
            margin-top: 20px;
        }
        .mt-4 { margin-top: 1.5rem; }
        .p-4 { padding: 1.5rem; }
        .border { border: 1px solid #000; }
        .rounded { border-radius: 0.25rem; }
        .mb-4 { margin-bottom: 1.5rem; }
        .mt-5 { margin-top: 3rem; }
    </style>
</head>
<body>
    <div class="container mt-4 p-4 border rounded">
        <h2 class="mb-4">Recibo de Anuidade</h2>
        <p><strong>Nome:</strong> {{ $name }}</p>
        <p><strong>CPF:</strong> {{ $cpf }}</p>
        <p><strong>Data de Pagamento:</strong> {{ $payment_date }}</p>
        <p><strong>Validade até:</strong> {{ $valid_until }}</p>
        <p><strong>Valor:</strong> R$ {{ $amount }}</p>
        <br><br>
        <p class="mt-5">_______________________________________</p>
        <p>Assinatura</p>
    </div>
</body>
</html>
