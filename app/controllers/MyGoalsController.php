<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Goal;
use Models\GoalAssignment;

class MyGoalsController extends Controller
{
    public function index()
    {
        $this->requireRole(['seller','manager','admin']);
        $me = Auth::user();
        $assign = new GoalAssignment();
        $items = $assign->listForUser((int)($me['id'] ?? 0), 200, 0);

        // Simple per-item computed fields
        $today = date('Y-m-d');
        foreach ($items as &$it) {
            $diasTotais = max(1, (strtotime($it['data_fim']) - strtotime($it['data_inicio']))/86400 + 1);
            $diasPassados = max(1, (min(strtotime($today), strtotime($it['data_fim'])) - strtotime($it['data_inicio']))/86400 + 1);
            $diasRestantes = max(0, $diasTotais - $diasPassados);
            $valorAtual = (float)($it['progresso_atual'] ?? 0);
            $valorMeta = (float)($it['valor_meta'] ?? 0);
            $mediaDiaria = $valorAtual / $diasPassados;
            $previsaoFinal = $mediaDiaria * $diasTotais;
            $percentualAtingido = $valorMeta>0 ? ($valorAtual / $valorMeta * 100.0) : 0;
            $valorNecessarioPorDia = $diasRestantes>0 ? max(0, ($valorMeta - $valorAtual) / $diasRestantes) : 0;
            $it['_calc'] = compact('diasTotais','diasPassados','diasRestantes','mediaDiaria','previsaoFinal','percentualAtingido','valorNecessarioPorDia');
        }
        unset($it);

        // Injetar meta GLOBAL (50k) como item junto com as demais
        $from = date('Y-m-01');
        $to = date('Y-m-t');
        $globalTargetUsd = 50000.0;
        $globalActualUsd = (new Goal())->salesTotalUsd($from, $to, null);
        // cálculos iguais aos demais itens
        $today = date('Y-m-d');
        $diasTotais = max(1, (strtotime($to) - strtotime($from))/86400 + 1);
        $diasPassados = max(1, (min(strtotime($today), strtotime($to)) - strtotime($from))/86400 + 1);
        $diasRestantes = max(0, $diasTotais - $diasPassados);
        $mediaDiaria = $globalActualUsd / $diasPassados;
        $previsaoFinal = $mediaDiaria * $diasTotais;
        $percentualAtingido = $globalTargetUsd>0 ? ($globalActualUsd / $globalTargetUsd * 100.0) : 0;
        $valorNecessarioPorDia = $diasRestantes>0 ? max(0, ($globalTargetUsd - $globalActualUsd) / $diasRestantes) : 0;
        $globalItem = [
            'titulo' => 'Meta Global 50k',
            'tipo' => 'global',
            'data_inicio' => $from,
            'data_fim' => $to,
            'moeda' => 'USD',
            'valor_meta' => $globalTargetUsd,
            'progresso_atual' => $globalActualUsd,
            '_calc' => compact('diasTotais','diasPassados','diasRestantes','mediaDiaria','previsaoFinal','percentualAtingido','valorNecessarioPorDia'),
        ];
        array_unshift($items, $globalItem);

        $this->render('my_goals/index', [
            'title' => 'Minhas Metas',
            'items' => $items,
        ]);
    }
}
