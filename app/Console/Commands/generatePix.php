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
        $email = config('colony.pix.email');

        $pix = Pix::make(
            TypeKey::PHONE,
            config('colony.pix.phone'),
            config('colony.pix.amount'),
            config('colony.pix.name'),
            config('colony.pix.cpf'),
            config('colony.pix.city'),
            'Pagamento mensal',
            false
        );

        // ──────────────────────────────────────────────
        // MODO CID (recomendado — funciona no Outlook)
        // Descomente o bloco "MODO BASE64" abaixo e
        // comente este bloco para reverter.
        // ──────────────────────────────────────────────

        // ─── MODO BASE64 (não funciona no Outlook) ───
        // $qrCode = $pix->getQrCode();
        //
        // Mail::send([], [], function ($message) use ($email, $qrCode) {
        //     $vencimento = Carbon::now()->day(29)->format('d/m/Y');
        //
        //     $message->to($email)
        //         ->from(
        //             config('mail.from.author'),
        //             config('mail.from.name')
        //         )
        //         ->subject('💳 Mensalidade disponível para pagamento');
        //
        //     $message->html("
        //         <h2>Olá! 👋</h2>
        //
        //         <p>Segue abaixo o QR Code referente à <strong>mensalidade da Colônia de Pescadores</strong>.</p>
        //
        //         <p>📅 <strong>Vencimento:</strong> {$vencimento}</p>
        //
        //         <p>💰 <strong>Valor:</strong> R$ " . number_format(config('colony.pix.amount'), 2, ',', '.') . "</p>
        //
        //         <p>Para realizar o pagamento, basta escanear o QR Code abaixo:</p>
        //
        //         <img src='{$qrCode}' style='width:250px; height:250px;' />
        //
        //         <h3>
        //             <strong>
        //                 🔑 Chave pix: " . config('colony.pix.phone') . "
        //             </strong>
        //         </h3>
        //     ");
        // });
        // ─── FIM MODO BASE64 ───

        // ─── MODO CID ───
        $qrCodeBinary = $pix->getQrCode('png', false);

        Mail::send([], [], function ($message) use ($email, $qrCodeBinary) {
            $vencimento = Carbon::now()->day(29)->format('d/m/Y');

            $message->to($email)
                ->from(
                    config('mail.from.author'),
                    config('mail.from.name')
                )
                ->subject('Mensalidade disponivel para pagamento');

            $message->html("
                <h2>Ola!</h2>

                <p>Segue abaixo o QR Code referente a <strong>mensalidade da Colonha de Pescadores</strong>.</p>

                <p><strong>Vencimento:</strong> {$vencimento}</p>

                <p><strong>Valor:</strong> R$ " . number_format(config('colony.pix.amount'), 2, ',', '.') . "</p>

                <p>Para realizar o pagamento, basta escanear o QR Code abaixo:</p>

                <img src='cid:qr-code-pix.png' style='width:250px; height:250px;' />

                <h3>
                    <strong>
                        Chave pix: " . config('colony.pix.phone') . "
                    </strong>
                </h3>
            ");

            $message->embedData($qrCodeBinary, 'qr-code-pix.png', 'image/png');
        });
        // ─── FIM MODO CID ───

        $this->info('QRCode enviado com sucesso!');
    }
}
