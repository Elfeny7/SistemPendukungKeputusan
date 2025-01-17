<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Method;
use Hamcrest\Arrays\IsArray;

class ElectreController extends Controller
{
  public function index(Request $request){
    if(isset($request)){
      if($request->get('id')){
        if(is_array($request->get('alternatives'))){
          return view('content.electre.index',[
            'title' => 'ELECTRE',
            'active' => 'electre',
            'alternatives' => $request->get('alternatives'),
            'criterias' => $request->get('criterias'),
            'values' => $request->get('values'),
            'weights' => $request->get('weights'),
            'id' => $request->get('id')
          ]);
        } else {
          return view('content.electre.index',[
            'title' => 'ELECTRE',
            'active' => 'electre',
            'alternatives' => json_decode($request->get('alternatives')),
            'criterias' => json_decode($request->get('criterias')),
            'values' => json_decode($request->get('values')),
            'weights' => json_decode($request->get('weights')),
            'id' => $request->get('id')
          ]
          );
        }
        
      }
      return view('content.electre.index',[
        'title' => 'ELECTRE',
        'active' => 'electre',
        'alternatives' => $request->get('alternatives'),
        'criterias' => $request->get('criterias'),
        'values' => $request->get('values'),
        'weights' => $request->get('weights')
      ]
      );
    } else {
      return view('content.electre.index',[
        'title' => 'ELECTRE',
        'active' => 'electre',
      ]
      );
    }
  }

  public function process(Request $request){
    $alternatives = $request->post('alternatives');
    $criterias = $request->post('criterias');
    $values = $request->post('values');
    $weights = $request->post('weights');

    $normalizations = $this->normalization($values);
    $preferenceMatrix = $this->preferenceMatrix($normalizations, $weights);
    $concordanceIndex = $this->concordanceIndex($preferenceMatrix);
    $discordanceIndex = $this->discordanceIndex($preferenceMatrix);
    $concordanceMatrix = $this->concordanceMatrix($concordanceIndex, $weights);
    $discordanceMatrix = $this->discordanceMatrix($discordanceIndex, $preferenceMatrix);
    $concordanceThreshold = $this->concordanceThreshold($concordanceMatrix);
    $discordanceThreshold = $this->discordanceThreshold($discordanceMatrix);
    $concordanceDominant = $this->concordanceDominant($concordanceThreshold, $concordanceMatrix);
    $discordanceDominant = $this->discordanceDominant($discordanceThreshold, $discordanceMatrix);
    $agregationDominant = $this->agregationDominant($concordanceDominant, $discordanceDominant);
    return view('content.electre.process',[
      'alternatives' => $alternatives,
      'criterias' => $criterias,
      'values' => $values,
      'weights' => $weights,
      'normalizations' => $normalizations,
      'preferenceMatrix' => $preferenceMatrix,
      'concordanceIndex' => $concordanceIndex,
      'discordanceIndex' => $discordanceIndex,
      'concordanceMatrix' => $concordanceMatrix,
      'discordanceMatrix' => $discordanceMatrix,
      'concordanceDominant' => $concordanceDominant,
      'discordanceDominant' => $discordanceDominant,
      'agregationDominant' => $agregationDominant,
      'id' => $request->post('id'),
      'title' => 'ELECTRE (Process)',
      'active' => 'electre'
    ]);

  }

  public function normalization($values){
    $normalizations = $values;
    $sum = array_fill(0, count($values[0]), 0);
    for($i = 0; $i < count($values); $i++){
      for($j = 0; $j < count($values[0]); $j++){
        $sum[$j] += (pow($values[$i][$j], 2));
      }
    }
    for($i = 0; $i < count($values); $i++){
      for($j = 0; $j < count($values[0]); $j++){
        $normalizations[$i][$j] = number_format($values[$i][$j] / sqrt($sum[$j]), 4);
      }
    }
    return $normalizations;
  }

  public function preferenceMatrix($normalizations, $weights){
    $preferenceMatrix = $normalizations;
    for($i = 0; $i < count($normalizations); $i++){
      for($j = 0; $j < count($normalizations[0]); $j++){
        $preferenceMatrix[$i][$j] *= $weights[$j];
      }
    }
    return $preferenceMatrix;
  }

  public function concordanceIndex($preferenceMatrix){

    $concordanceIndex = array();
    $index = '';
    for($i = 0; $i < count($preferenceMatrix); $i++){
      if($index != $i){
        $index = $i;
        $concordanceIndex[$i] = array();
      }

      for($j = 0; $j < count($preferenceMatrix); $j++){
        if($i != $j){
          for($k = 0; $k < count($preferenceMatrix[0]); $k++){
            if(!isset($concordanceIndex[$i][$j])){
              $concordanceIndex[$i][$j] = array();
            }
            if($preferenceMatrix[$i][$k] >= $preferenceMatrix[$j][$k]){
              array_push($concordanceIndex[$i][$j], $k);
            }
          }
        }
      }
    }

  return $concordanceIndex;

  }

  public function discordanceIndex($preferenceMatrix){

    $discordanceIndex = array();
    $index = '';
    for($i = 0; $i < count($preferenceMatrix); $i++){
      if($index != $i){
        $index = $i;
        $discordanceIndex[$i] = array();
      }

      for($j = 0; $j < count($preferenceMatrix); $j++){
        if($i != $j){
          for($k = 0; $k < count($preferenceMatrix[0]); $k++){
            if(!isset($discordanceIndex[$i][$j])){
              $discordanceIndex[$i][$j] = array();
            }
            if($preferenceMatrix[$i][$k] < $preferenceMatrix[$j][$k]){
              array_push($discordanceIndex[$i][$j], $k);
            }
          }
        }
      }
    }

  return $discordanceIndex;

  }

  public function concordanceMatrix($concordanceIndex, $weights){
    $concordanceMatrix = array();
    $index = '';

    for($i = 0; $i < count($concordanceIndex); $i++){
      if($index != $i){
        $index = $i;
        $concordanceMatrix[$i] = array();
      }

      for($j = 0; $j < count($concordanceIndex); $j++){
        if($i != $j && count($concordanceIndex[$i][$j])){
          foreach($concordanceIndex[$i][$j] as $con){
            $concordanceMatrix[$i][$j] = (isset($concordanceMatrix[$i][$j]) ? $concordanceMatrix[$i][$j] : 0) + (int) $weights[$con];
          }
        }
      }
    }
    return $concordanceMatrix;
  }

  public function discordanceMatrix($discordanceIndex, $preferenceMatrix){
    
    $discordanceMatrix = array();
    $index = '';

    for($i = 0; $i < count($discordanceIndex); $i++){
      if($index != $i){
        $index = $i;
        $discordanceMatrix[$i] = array();
      }

      for($j = 0; $j < count($discordanceIndex); $j++){
        if($i != $j){
          $max_d = 0;
          $max_j = 0;
            foreach($discordanceIndex[$i][$j] as $disc){
              if($max_d < abs($preferenceMatrix[$i][$disc] - $preferenceMatrix[$j][$disc])){
                $max_d = abs($preferenceMatrix[$i][$disc] - $preferenceMatrix[$j][$disc]);
              }
            }
            for($k = 0; $k < count($preferenceMatrix[0]); $k++){
              if($max_j < abs($preferenceMatrix[$i][$k] - $preferenceMatrix[$j][$k])){
                $max_j = abs($preferenceMatrix[$i][$k] - $preferenceMatrix[$j][$k]);
              }
            }
            $discordanceMatrix[$i][$j] = $max_d / $max_j;
        }
      }
    }
    return $discordanceMatrix;
  }

  public function concordanceThreshold($concordanceMatrix){

    $sigma_c = 0;
    foreach($concordanceMatrix as $k => $cl){
      foreach($cl as $l => $value){
        $sigma_c += $value;
      }
    }
    $threshold_c = $sigma_c / (count($concordanceMatrix) * (count($concordanceMatrix) - 1));

    return $threshold_c;
  }

  public function discordanceThreshold($discordanceMatrix){
    $sigma_d = 0;
    foreach($discordanceMatrix as $k => $dl){
      foreach($dl as $l => $value){
        $sigma_d += $value;
      }
    }
    $threshold_d = $sigma_d / (count($discordanceMatrix) * (count($discordanceMatrix) - 1));

    return $threshold_d;
  }

  public function concordanceDominant($concordanceThreshold, $concordanceMatrix){
    $cd = array();
      foreach($concordanceMatrix as $k => $cl){
        $cd[$k] = array();
        foreach($cl as $l => $value){
          $cd[$k][$l] = ($value >= $concordanceThreshold ? 1 : 0);
        }
      }
    return $cd;
  }

  public function discordanceDominant($discordanceThreshold, $discordanceMatrix){
    $dd = array();
      foreach($discordanceMatrix as $k => $cl){
        $dd[$k] = array();
        foreach($cl as $l => $value){
          $dd[$k][$l] = ($value >= $discordanceThreshold ? 1 : 0);
        }
      }
    return $dd;
  }

  public function agregationDominant($concordanceDominant, $discordanceDominant){
    $ad = array();
      foreach($concordanceDominant as $k => $sl){
        $ad[$k] = array();
        foreach($sl as $l => $value){
          $ad[$k][$l] = $concordanceDominant[$k][$l] * $discordanceDominant[$k][$l];
        }
      }
    return $ad;
  }
  
  public function save(Request $request){
    if($request->post('id')){
      $method = Method::find($request->post('id'));
      $values = $request->post('values');
      $criterias = $request->post('criterias');
      $alternatives = $request->post('alternatives');
      $weights = $request->post('weights');
      $method->name = 'ELECTRE';
      $method->data = $values;
      $method->alternatives = $alternatives;
      $method->criterias = $criterias;
      $method->weights = $weights;
      $method->user_id = Auth::user()->id;
      $method->save();
      return redirect('/history');
    } else {
      $method = new Method();
      $values = $request->post('values');
      $criterias = $request->post('criterias');
      $alternatives = $request->post('alternatives');
      $weights = $request->post('weights');
      $method->name = 'ELECTRE';
      $method->data = $values;
      $method->alternatives = $alternatives;
      $method->criterias = $criterias;
      $method->weights = $weights;
      $method->user_id = Auth::user()->id;
      $method->save();
      return redirect('/history')->with('id', $method->id);
    }
  }
}
