<?php

namespace App\Http\Controllers;
use App\Apartment;
use App\Tag;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    // INDEX
    public function index() {
      //recupero gli appartamenti nel db
      $apartments = Apartment::all();
      $tags = Tag::all();
      return view('guest.index', compact('apartments','tags'));
    }

    //funzione per gestire coordinate e dati inviati da un utente
    //PARAMETRO: $request sono i dati che ricevo da una chiamata ajax
    //RETURN: Json coi dati recuperati dal db e filtrati da mostrare
    public function coordinatesHandler(Request $request){

      $data = $request->all();
      $latitude = $data['latitude'];
      $longitude = $data['longitude'];
      $number_of_rooms = $data['rangeRooms'];
      $number_of_beds = $data['rangeBeds'];
      if (!empty($data['arrayTags'])) {
        $array_tags = $data['arrayTags'];
      }else {
        $array_tags = [];
      }
      $distance_research = $data['distance'];
      //formatto cordinate per la funzione points_distance
      $user_coordinates = $latitude . ',' . $longitude;
      //inizializzo l'array dove salvero i dati da mostrare
      $array_results = [];

      //recupero gli appartamenti nel db
      $apartments = Apartment::all();
      //ciclo per filtrare gli appartamenti
      foreach ($apartments as $apartment) {
        $apartment_rooms = $apartment->number_of_rooms;
        $apartment_beds = $apartment->number_of_beds;
        $apartment_latitude = $apartment->latitude;
        $apartment_longitude = $apartment->longitude;
        $apartment_coordinates = $apartment_latitude . ',' . $apartment_longitude;
        $distance = $this->points_distance($user_coordinates,$apartment_coordinates);
        //se la distanza dell'appartamento è minore di quella settata
        if ( $distance <= $distance_research ) {
          //se il numero di stanze e il numero dei letti sono maggiori di quelli settati
          if ( ($apartment_rooms >= $number_of_rooms) && ($apartment_beds >= $number_of_beds) ) {
            //se i tags selezionati sono presenti

            if (empty($array_tags)) {
              //mostro l'appartamento
              $array_results[] = [
                'distance' => $distance,
                'apartment' => $apartment
              ];
            }else {
              
            }

          }
        }
      }

      //ordino l'array basandomi sulla distanza dal punto di interesse, in ordine crescente
      usort($array_results, function($a, $b) {
        return $a['distance'] <=> $b['distance'];
      });


      // ritorno in formato json l'array dei risultati
      return response()->json(['success'=>$array_results]);
    }

    //funzione per calcolare la distanza tra due punti
    //PARAMETRI: due stringhe contenenti entrambe latitudine e longitudine separate da virgola
    //RETURN: un float la distanza tra i due punti
    public function points_distance ( $coordinate_a, $coordinate_b ) {

      $RAGGIO_QUADRATICO_MEDIO = 6372.795477598;
      list($decLatA, $decLonA) = array_map('trim', explode(',', $coordinate_a));
      list($decLatB, $decLonB) = array_map('trim', explode(',', $coordinate_b));
      $radLatA = pi() * $decLatA / 180;
      $radLonA = pi() * $decLonA / 180;
      $radLatB = pi() * $decLatB / 180;
      $radLonB = pi() * $decLonB / 180;
      $phi = abs($radLonA - $radLonB);
      $P = acos (
            (sin($radLatA) * sin($radLatB)) +
            (cos($radLatA) * cos($radLatB) * cos($phi))
      );
      return $P * $RAGGIO_QUADRATICO_MEDIO;
    }



}
