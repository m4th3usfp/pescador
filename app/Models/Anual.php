<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Anual extends Model
{
      // Nome da tabela
      protected $table = 'anual';

      // Campos que podem ser preenchidos em massa
      protected $fillable = [
          'amount',
          'date',
          'active',
      ];
  
      // Caso você queira que o Laravel trate 'data' como um campo de data
      protected $dates = [
          'data',
      ];
  
      // Se você **não** estiver usando created_at e updated_at
    //   public $timestamps = false;
}
