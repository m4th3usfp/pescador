<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use WandesCardoso\Pix\Facades\Pix;
use WandesCardoso\Pix\Enums\TypeKey;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class generatePix extends Command
{
    protected $signature = 'app:generate-pix';
    protected $description = 'gerar QRCode para pix';

    public function handle()
    {
        $email = 'raidar-dabiane@hotmail.com';

        $pix = Pix::make(
            TypeKey::PHONE,
            '+5519999833188',
            300.00,
            'Matheus',
            '12345678901',
            'FRUTAL',
            'Pagamento mensal',
            false
        );

        $qrCode = $pix->getQrCode();

        // remove base64 prefixo
        $base64 = str_replace('data:image/png;base64,', '', $qrCode);
        $image = base64_decode($base64);

        // salva temporário
        $path = storage_path('app/public/qrcode.png');
        file_put_contents($path, $image);

        // cria URL pública
        $url = asset('storage/qrcode.png');
        // dd($url);

        Mail::send([], [], function ($message) use ($email, $url) {

            $vencimento = Carbon::now()->day(29)->format('d/m/Y');

            $message->to($email)
                ->from('matheuspizzinato975@gmail.com', 'Colônia de Pescadores')
                ->subject('💳 Mensalidade disponível para pagamento');

            $message->html("
                <h2>Olá! 👋</h2>
        
                <p>Segue abaixo o QR Code referente à <strong>mensalidade da Colônia de Pescadores</strong>.</p>
        
                <p>📅 <strong>Vencimento:</strong> {$vencimento}</p>
                <p>💰 <strong>Valor:</strong> R$ 300,00</p>
        
                <p>Para realizar o pagamento, basta escanear o QR Code abaixo:</p>
        
                <img src='{$url}' style='width:250px; height:250px;' />
        
                <h3><strong>🔑 Chave pix: +5519999833188</strong></h3>
            ");
        });
        $this->info('📧 QRCode enviado com sucesso!');
    }
}
