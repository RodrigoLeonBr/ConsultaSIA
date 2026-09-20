@extends('layouts.modern')
@section('title', 'Produção Quadrimestral')
@section('content')
  <form method="POST" action="{{ route('relatorios.quadrimestral.gerar') }}">
    @csrf
    <select name="ano">@foreach($anos as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach</select>
    <select name="quadrimestre">@foreach($quadrimestres as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
    <button type="submit">Aplicar</button>
  </form>
  @isset($resultado)
    @foreach($resultado['secoes'] as $secao)
      <h2>{{ $secao['tipo'] }}</h2>
      <table>
        @foreach($secao['linhas'] as $no)
          <tr data-node-id="{{ $no['node_id'] }}" data-parent-id="{{ $no['parent_id'] }}" data-level="{{ $no['level'] }}">
            <td>{{ $no['cod'] }} · {{ $no['desc'] }}</td>
          </tr>
        @endforeach
      </table>
    @endforeach
  @endisset
@endsection
