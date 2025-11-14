<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
# pescador

as tabelas que vou usar no DB sao: anuidade, global, nome_colonia, pescadores, pescadores_arquivos, usuarios;

colca select apos login para usuarios que podem acessar mais de uma cidade;
consertar a logica do cadastro que ta dando record_number repetido;

                        <div class="d-flex align-items-end justify-content-between">
                            <div>
                                <label for="caepf_password" class="form-label">Senha CAEPF</label>
                                <input type="password" class="form-control" id="caepf_password" name="caepf_password" value="{{ $cliente->caepf_password ?? '' }}">
                            </div>
                            @method('PUT')
                            @csrf
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary px-5 py-2">
                                    Salvar
                                </button>

                                @else

                                <button type="submit" class="btn btn-primary px-5 py-2">
                                    Cadastrar pescador
                                </button>
                            </div>
                            @endif
                        </div>

                        16/10     retirei o nao alfabetizadao a pedido do Lucas pq nao precisava
                        <!-- <a href="{{ route('non_Literate_Affiliation', $cliente->id) }}" class="list-group-item list-group-item-action">Declaração de filiação - MPA (não alfabetizado)</a> -->




old_payment  new_payment    user_id    user    city_id     record_number      fisher_name    
2025-09-02	 2026-08-19	 	   9	   LUCAS   3	       3592	              AILTO ROSA SANTANA //    
2025-11-01	 2026-08-16	 	   9	   LUCAS   3	       619	              PATRICIA FERREIRA DA SILVA  //
2025-09-03	 2026-08-01	 	   21	   LUAN    3	       213	              SOLANGE CORA DE AVILA //       
2025-10-02	 2026-07-13	 	   9	   LUCAS   3	       18	              ADRIANO QUINTILIANO MOREIRA //
2025-10-16	 2026-05-26	 	   9	   LUCAS   3	       879	              ZILDA FARIA DA SILVA //         
2025-09-09	 2026-04-01	 	   9	   LUCAS   3	       1680	              FERNANDO ALVES GUERREIRO //         
2025-02-13	 2026-03-13	 	   9	   LUCAS   3	       3699	              MARIA CLEIDE DOS SANTOS GUEDES //
2025-08-06	 2026-01-23	 	   21	   LUAN    3	       3002	              OZANA PERES CHAGAS //       
2025-10-22	 2025-10-23	       9	   LUCAS   3           527                LUAN RICARDO PADILHA SALWANININ //



city_id     record_number      fisher_name
   3	       3592	              AILTO ROSA SANTANA		
   3	       619	              PATRICIA FERREIRA DA SILVA		
   3	       213	              SOLANGE CORA DE AVILA		
   3	       18	              ADRIANO QUINTILIANO MOREIRA		
   3	       879	              ZILDA FARIA DA SILVA 		
   3	       1680	              FERNANDO ALVES GUERREIRO		
   3	       3699	              MARIA CLEIDE DOS SANTOS GUEDES		
   3	       3002	              OZANA PERES CHAGAS
   3           527                LUAN RICARDO PADILHA SALWANININ 




   INSERT INTO payment_record (
    old_payment,
    new_payment,
    user_id,
    "user",
    city_id,
    record_number,
    fisher_name
) VALUES
('2025-09-02', '2026-08-19', 10,  'LUCAS', 3, 3592, 'AILTO ROSA SANTANA'),
('2025-11-01', '2026-08-16', 10,  'LUCAS', 3, 619,  'PATRICIA FERREIRA DA SILVA'),
('2025-09-03', '2026-08-01', 12,  'LUAN',  3, 213,  'SOLANGE CORA DE AVILA'),
('2025-10-02', '2026-07-13', 10,  'LUCAS', 3, 18,   'ADRIANO QUINTILIANO MOREIRA'),
('2025-10-16', '2026-05-26', 10,  'LUCAS', 3, 879,  'ZILDA FARIA DA SILVA'),
('2025-09-09', '2026-04-01', 10,  'LUCAS', 3, 1680, 'FERNANDO ALVES GUERREIRO'),
('2025-02-13', '2026-03-13', 10,  'LUCAS', 3, 3699, 'MARIA CLEIDE DOS SANTOS GUEDES'),
('2025-08-06', '2026-01-23', 12,  'LUAN',  3, 3002, 'OZANA PERES CHAGAS'),
('2025-10-22', '2025-10-23', 10,  'LUCAS', 3, 527,  'LUAN RICARDO PADILHA SALWANININ');





SELECT 
    name,
    record_number,
    city_id,
    expiration_date,
    COUNT(*) AS desativados
FROM fishermen
WHERE expiration_date < '2025-09-18'
GROUP BY name, record_number, city_id, expiration_date;


SELECT 
    record_number,
    name, city_id,
    active,
    COUNT(*) AS total_repetidos
FROM fishermen
GROUP BY record_number, name, city_id, active
HAVING COUNT(*) > 1 and active = 'true'
ORDER BY total_repetidos DESC;







    public function receiveAnnual($id)
    {
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);
        $user = Auth::user();

        // Ajusta o city_id do usuário com base na cidade da sessão
        switch (session('selected_city')) {
            case 'Frutal':
                $user->city_id = 1;
                break;
            case 'Uberlandia':
                $user->city_id = 2;
                break;
            default:
                $user->city_id = 3;
                break;
        }

        Carbon::setLocale('pt_BR');
        $now = Carbon::now();
        $currentExpiration = Carbon::parse($fisherman->expiration_date);

        // dump('currentExpiration'.' '.$currentExpiration);
        
        $currentExpiration_2 = Carbon::parse($fisherman->expiration_date);

        // dump('currentExpiration_2'.' '.$currentExpiration_2); //2025

        $newExpiration = $currentExpiration_2->greaterThan($now)
            ? $currentExpiration_2->addYear()
            : $now->copy()->addYear();
        // Atualiza vencimento no banco
        
        // dump('$new'.' '. $newExpiration);
        // dump('currentExpiration (apos condição)'.' '.$currentExpiration);
        // dump('currentExpiration_2 (apos condição)'.' '.$currentExpiration_2); //2025
        // $fisherman->save();
        
        // Cria o registro de pagamento
        Payment_Record::create([
            'fisher_name'   => $fisherman->name,
            'record_number' => $fisherman->id,
            'city_id'       => $fisherman->city_id, // ✅ usa o city_id atualizado do usuário
            'user'          => $user->name,
            'user_id'       => $user->city_id,      // ✅ deve ser o ID do usuário, não o city_id
            'old_payment'   => $currentExpiration->format('Y/m/d'),
            'new_payment'   => $newExpiration->format('Y/m/d'),
        ]);
        // dd($vetor);

        $fisherman->expiration_date = $newExpiration->format('Y-m-d');

        // Busca as configurações do dono com base na cidade atualizada
        $OwnerSettings = Owner_Settings_Model::where('city_id', $user->city_id)->first();
        if (!$OwnerSettings) {
            abort(404, 'Informações da colônia não encontradas para esta cidade.');
        }

        // Prepara os dados para o recibo
        $data = [
            'NAME'           => $fisherman->name,
            'CITY'           => session('selected_city'),
            'PAYMENT_DATE'   => mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
            'VALID_UNTIL'    => mb_strtoupper($newExpiration->translatedFormat('d \d\e F \d\e Y'), 'UTF-8'),
            'AMOUNT'         => $OwnerSettings->amount,
            'EXTENSE'        => $OwnerSettings->extense,
            'ADDRESS'        => $OwnerSettings->address,
            'NEIGHBORHOOD'   => $OwnerSettings->neighborhood ?? '',
            'ADDRESS_CEP'    => $OwnerSettings->postal_code ?? '',
            'PRESIDENT_NAME' => $OwnerSettings->president_name,
        ];

        // Define o template conforme a cidade
        $templatePath = match ($user->city_id) {
            1 => resource_path('templates/recibo_1.docx'),
            2 => resource_path('templates/recibo_2.docx'),
            3 => resource_path('templates/recibo_3.docx'),
        };

        // dd($templatePath);
        // Gera o DOCX
        $template = new TemplateProcessor($templatePath);
        foreach ($data as $key => $value) {
            $template->setValue($key, $value);
        }

        $fileName = 'recibo_anuidade_' . $fisherman->name . ' ' .
            mb_strtoupper($now->translatedFormat('d \d\e F \d\e Y')) . '.docx';
        $filePath = storage_path('app/public/' . $fileName);

        $template->saveAs($filePath);

        return response()->download($filePath);
    }









6	THIAGO FRANCISCO DA COSTA	7050	2	RALIME	2	2025-11-28	2026-11-28	2025-11-13 12:00:50.000	2025-11-13 12:00:50.000
7	THIAGO FRANCISCO DA COSTA	7050	2	RALIME	2	2025-11-28	2026-11-28	2025-11-13 12:01:40.000	2025-11-13 12:01:40.000
8	THIAGO FRANCISCO DA COSTA	7050	2	RALIME	2	2025-11-28	2026-11-28	2025-11-13 12:03:01.000	2025-11-13 12:03:01.000
9	THIAGO FRANCISCO DA COSTA	7050	2	RALIME	2	2025-11-28	2026-11-28	2025-11-13 12:05:14.000	2025-11-13 12:05:14.000
11	LUIS DOMINGOS TEIXEIRA 	5216	3	LUCAS	3	2025-11-22	2026-11-22	2025-11-13 16:29:37.000	2025-11-13 16:29:37.000
4	THIAGO FRANCISCO DA COSTA	7050	2	RALIME	2	2025-11-28	2026-11-28	2025-11-13 10:55:41.000	2025-11-13 10:55:41.000
1	JELIA DA SILVA SANTOS	1992	2	RALIME	2	2025-12-04	2026-12-04	2025-11-11 14:56:28.000	2025-11-11 14:56:28.000
3	WILLIAN CUNHA SOARES	3035	1	daniely	1	2025-03-18	2026-03-18	2025-11-12 09:41:58.000	2025-11-12 09:41:58.000