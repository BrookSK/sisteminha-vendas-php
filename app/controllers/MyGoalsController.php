<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Goal;
use Models\GoalAssignment;
use Models\Setting;
use Models\Commission;

class MyGoalsController extends Controller
{
    public function index()
    {
        $this->requireRole(['seller','trainee','manager','admin']);
        $me = Auth::user();
        $assign = new GoalAssignment();
        $uid = (int)($me['id'] ?? 0);
        // Removida auto-atribuição de metas individuais ao criador; atribuições devem ser feitas explicitamente

        // Carregar itens após possíveis auto-atribuições
        $items = $assign->listForUser($uid, 200, 0);
        // Recalcular progresso atual a partir do mesmo cálculo de comissões (alinhado ao dashboard)
        foreach ($items as &$it) {
            $from = (string)($it['data_inicio'] ?? date('Y-m-01'));
            $to = (string)($it['data_fim'] ?? date('Y-m-t'));
            $comm = new Commission();
            $calc = $comm->computeRange($from.' 00:00:00', $to.' 23:59:59');
            $mine = null;
            foreach (($calc['items'] ?? []) as $row) {
                if ((int)($row['vendedor_id'] ?? 0) === (int)$uid) { $mine = $row; break; }
            }
            $real = (float)($mine['bruto_total'] ?? 0.0);
            if ($real <= 0.0) {
                // Segundo caminho: somar por fonte com o mesmo período
                try {
                    $src = (new Commission())->sellerSourceSums((int)$uid, $from.' 00:00:00', $to.' 23:59:59');
                    $real = (float)($src['total']['bruto_total'] ?? 0.0);
                } catch (\Throwable $e) { /* ignore */ }
            }
            if ($real <= 0.0) {
                // Último fallback: sumarização direta
                try { $real = (new Goal())->salesTotalUsd($from, $to, $uid); } catch (\Throwable $e) { /* ignore */ }
            }
            $assign->updateProgress((int)$it['id_meta'], $uid, (float)$real);
            $it['progresso_atual'] = (float)$real;
        }
        unset($it);

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

        // Injetar meta GLOBAL (50k) usando período padrão do sistema
        try { [$from, $to] = (new Setting())->currentPeriod(); } catch (\Throwable $e) { $from = date('Y-m-10'); $to = date('Y-m-09', strtotime('first day of next month')); }
        $globalTargetUsd = 50000.0;
        $commG = new Commission();
        $calcG = $commG->computeRange($from.' 00:00:00', $to.' 23:59:59');
        $globalActualUsd = (float)($calcG['team']['team_bruto_total'] ?? 0.0);
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
