<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\Report;
use Models\Commission;
use Models\Setting;
use Models\Notification;

class DashboardController extends Controller
{
    public function index()
    {
        $setting = new Setting();
        $rate = (float)$setting->get('usd_rate', '5.83');
        // Período corrente (configurável; default 10->9)
        [$from, $to] = $setting->currentPeriod();
        $me = Auth::user();
        $role = $me['role'] ?? 'seller';
        $sellerId = (in_array($role, ['seller','trainee','manager'], true)) ? (int)($me['id'] ?? 0) : null;

        // Totais do período via Report (para contagem e lista) e comissões via Commission (para alinhamento de valores)
        $report = new Report();
        $totalCount = $report->countInPeriodAll($from, $to, $sellerId);

        // Comissão e totais alinhados à tela de comissões
        $comm = new Commission();
        $calc = $comm->computeRange($from.' 00:00:00', $to.' 23:59:59');
        // Derivar bruto e líquido (após custos) para os cards a partir do mesmo cálculo
        $sumBruto = 0.0; $sumLiquido = 0.0; $commissionTotalUSD = 0.0;
        if (in_array($role, ['seller','trainee','manager'], true)) {
            $mineFound = false;
            foreach (($calc['items'] ?? []) as $it) {
                if ((int)($it['vendedor_id'] ?? 0) === (int)$sellerId) {
                    $sumBruto = (float)($it['bruto_total'] ?? 0);
                    $sumLiquido = (float)($it['liquido_apurado'] ?? ($it['liquido_total'] ?? 0));
                    $commissionTotalUSD = (float)($it['comissao_final'] ?? 0);
                    $mineFound = true;
                    break;
                }
            }
            if (!$mineFound && $sellerId) {
                // Fallback: somar pelas fontes no mesmo período
                try {
                    $src = $comm->sellerSourceSums((int)$sellerId, $from.' 00:00:00', $to.' 23:59:59');
                    $sumBruto = (float)($src['total']['bruto_total'] ?? 0.0);
                    $sumLiquido = (float)($src['total']['liquido_total'] ?? 0.0);
                } catch (\Throwable $e) { /* ignore */ }
            }
        } else {
            foreach (($calc['items'] ?? []) as $it) {
                $sumBruto += (float)($it['bruto_total'] ?? 0);
                $sumLiquido += (float)($it['liquido_apurado'] ?? ($it['liquido_total'] ?? 0));
                $commissionTotalUSD += (float)($it['comissao_final'] ?? 0);
            }
        }
        $summary = ['total_bruto_usd' => $sumBruto, 'total_liquido_usd' => $sumLiquido];

        // Últimas vendas do dia (filtra por vendedor quando for seller)
        $recentToday = $report->recentTodayAll(10, $sellerId);

        // Notificações recentes (somente não lidas) para o usuário logado
        $notifModel = new Notification();
        $notificationsRecent = $notifModel->listUnreadForUser((int)($me['id'] ?? 0), 5, 0);
        $notificationsUnread = $notifModel->unreadCount((int)($me['id'] ?? 0));

        $this->render('dashboard/index', [
            'title' => 'Dashboard',
            'rate' => $rate,
            'period_from' => $from,
            'period_to' => $to,
            'summary' => $summary,
            'total_count' => $totalCount,
            'commission_total_usd' => $commissionTotalUSD,
            'recent_today' => $recentToday,
            'notifications_recent' => $notificationsRecent,
            'notifications_unread' => $notificationsUnread,
        ]);
    }
}
