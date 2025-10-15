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
        $sellerId = ($role === 'seller') ? (int)($me['id'] ?? 0) : null;

        // Sumário do período (filtra por vendedor quando for seller)
        $report = new Report();
        $summary = $report->summary($from, $to, $sellerId);
        $totalCount = $report->countInPeriodAll($from, $to, $sellerId);

        // Comissão do período
        $comm = new Commission();
        $calc = $comm->computeRange($from.' 00:00:00', $to.' 23:59:59');
        $commissionTotalUSD = 0.0;
        if ($role === 'seller') {
            foreach (($calc['items'] ?? []) as $it) {
                if ((int)($it['vendedor_id'] ?? 0) === (int)$sellerId) {
                    $commissionTotalUSD = (float)($it['comissao_final'] ?? 0);
                    break;
                }
            }
        } else {
            foreach (($calc['items'] ?? []) as $it) {
                $commissionTotalUSD += (float)($it['comissao_final'] ?? 0);
            }
        }

        // Últimas vendas do dia (filtra por vendedor quando for seller)
        $recentToday = $report->recentTodayAll(10, $sellerId);

        // Notificações recentes para o usuário logado
        $notifModel = new Notification();
        $notificationsRecent = $notifModel->listForUser((int)($me['id'] ?? 0), 5, 0);
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
