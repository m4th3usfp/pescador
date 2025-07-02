<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Fisherman;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;

class FishermanController extends Controller
{

    public function index()
    {

        $user = Auth::user();



        if (!$user->city_id) {

            return redirect()->route('login')->with('error', 'Cidade não associada ao usuário.');
        }



        $cityName = $user->city;



        $allowedCities = ['Frutal', 'Fronteira', 'Uberlandia'];



        if (!in_array($cityName, $allowedCities)) {

            return redirect()->route('login')->with('error', 'Cidade não permitida.');
        }



        $clientes = Fisherman::where('city_id', $user->city_id)->get();

        // dd($clientes);

        return view('listagem', compact('clientes'));
    }



    public function cadastro()
    {

        $maxRecordNumber = (int) Fisherman::max('record_number');

        $recordNumber = $maxRecordNumber + 1;

        // dd($maxRecordNumber, $recordNumber);

        return view('Cadastro', compact('recordNumber'));
    }


    public function store(Request $request)
    {



        $user = auth()->user();



        if (!$user) {

            return redirect()->route('login')->with('error', 'Você precisa estar logado para cadastrar um pescador.');
        }



        $data = $request->all();

        // dd($data['record_number']);

        $data['city_id'] = $user->city_id;




        if (!empty($data['expiration_date'])) {


            $data['expiration_date'] = Carbon::createFromFormat('d/m/Y', $data['expiration_date'])->format('Y/m/d');
        }


        // dd($data);




        $pescador = Fisherman::create($data);



        return redirect()->route('listagem')->with([

            'success' => 'Pescador cadastrado com sucesso!',

            'pescador' => $pescador->toArray()

        ]);
    }

    public function edit($id)
    {

        $cliente = Fisherman::findOrFail($id);

        $recordNumber = $cliente->record_number; // Mantém o número da ficha

        if (!empty($cliente->expiration_date)) {
            // dd($cliente->expiration_date);

            $cliente->expiration_date = Carbon::createFromFormat('Y-m-d', $cliente->expiration_date)->format('d/m/Y');
        }




        return view('Cadastro', compact('cliente', 'recordNumber'));
    }

    public function update(Request $request, $id)
    {
        $fisherman = Fisherman::findOrFail($id);

        $data = $request->all();

        // Converte a data do formato d/m/Y para Y-m-d
        if (!empty($data['expiration_date'])) {
            $data['expiration_date'] = Carbon::createFromFormat('d/m/Y', $data['expiration_date'])->format('Y-m-d');
            // dd($data['expiration_date']);
        }

        $fisherman->update($data);

        return redirect()->route('listagem')->with('success', 'Pescador atualizado com sucesso!');
    }

    public function destroy($id)
    {

        $fisherman = Fisherman::findOrFail($id);

        // dd($fisherman);

        $fisherman->delete();



        return redirect()->back();
    }

    public function logout()
    {

        Auth::logout();



        return redirect()->route('login')->with('success', 'Logout realizado com sucesso !');
    }

    public function receiveAnnual($id)
    {
        // Busca o pescador
        $fisherman = Fisherman::findOrFail($id);
        $userCity = Auth::user()->city;
        // dd($userCity);
        // Datas
        $now = Carbon::now();
        $currentExpiration = Carbon::parse($fisherman->expiration_date);
        
        $newExpiration = $currentExpiration->greaterThan($now)
        ? $currentExpiration->addYear()
        : $now->copy()->addYear();

        // Atualiza vencimento no banco
        $fisherman->expiration_date = $newExpiration->format('Y-m-d');
        $fisherman->save();
        
        // Dados do recibo
        $data = [
            'name' => $fisherman->name,
            'city' => $userCity,
            'payment_date' => $now->format('d/m/Y'),
            'valid_until' => $newExpiration->format('d/m/Y'),
        ];
        
        // Carrega o template .docx
        $templatePath = resource_path('templates/recibo_1.docx');
        $template = new TemplateProcessor($templatePath);
        
        // Preenche os campos
        foreach ($data as $key => $value) {
            $template->setValue($key, $value);
        }
        
        // Caminho temporário para salvar
        $fileName = 'recibo-anuidade-' . $fisherman->id . '.docx';
        $filePath = storage_path('app/public/' . $fileName);
        
        // Salva o novo .docx
        $template->saveAs($filePath);

        // Retorna como download e apaga depois de enviar
        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
