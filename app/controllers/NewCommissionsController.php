<?php
namespace Controllers;

use Core\Auth;
use Core\Controller;
use Models\Commission;
use Models\MonthlySnapshot;
use Models\NewCommission;
use Models\User;

class NewCommissionsController extends Controller
{
    public function index()
    {
        $this->requireRole(['seller','trainee','manager','admin','organic']);

        $me = Auth::user();
        $role = (string)($me['role'] ?? 'seller');

        $period = trim($_GET['period'] ?? Commission::defaultPeriod());
        $sellerId = isset($_GET['seller_id']) && $_GET['seller_id'] !== '' ? (int)$_GET['seller_id'] : null;

        if (in_array($role, ['seller','trainee','manager','organic'], true)) {
            $sellerId = (int)($me['id'] ?? 0) ?: null;
        }

        $commModel = new Commission();
        [$rangeFrom, $rangeTo] = $commModel->monthRange($period);
        $fromDate = substr($rangeFrom, 0, 10);
        $toDate = substr($rangeTo, 0, 10);

        $snap = new MonthlySnapshot();
        $companySnap = $snap->loadCompanyForPeriod($fromDate, $toDate);
        $items = [];
        $company = null;

        if ($companySnap) {
            $sellerSnaps = $snap->loadSellersForPeriod($fromDate, $toDate);
            foreach ($sellerSnaps as $row) {
                $extra = [];
                if (!empty($row['extra_json'])) {
                    $decoded = json_decode((string)$row['extra_json'], true);
                    if (is_array($decoded)) { $extra = $decoded; }
                }
                $nc = $extra['new_commission'] ?? null;
                if (!is_array($nc)) { continue; }

                $items[] = [
                    'seller_id' => (int)($row['seller_id'] ?? 0),
                    'seller_name' => (string)($row['seller_name'] ?? ''),
                    'seller_email' => (string)($row['seller_email'] ?? ''),
                    'seller_role' => (string)($row['seller_role'] ?? ''),
                    'seller_ativo' => (int)($row['seller_ativo'] ?? 0),
                    'bruto_total_brl' => (float)($nc['bruto_total_brl'] ?? 0),
                    'liquido_novo_brl' => (float)($nc['liquido_novo_brl'] ?? 0),
                    'percent' => (float)($nc['percent'] ?? 0),
                    'comissao_brl' => (float)($nc['comissao_brl'] ?? 0),
                ];
            }

            $extraCompany = [];
            if (!empty($companySnap['extra_json'])) {
                $decoded = json_decode((string)$companySnap['extra_json'], true);
                if (is_array($decoded)) { $extraCompany = $decoded; }
            }
            $company = $extraCompany['new_commission'] ?? null;
        } else {
            $calc = (new NewCommission())->computeRange($rangeFrom, $rangeTo, null);
            $items = $calc['items'] ?? [];
            $company = $calc['company'] ?? null;
        }

        if ($sellerId) {
            $items = array_values(array_filter($items, function($it) use ($sellerId) {
                return (int)($it['seller_id'] ?? 0) === (int)$sellerId;
            }));
        }

        $users = [];
        if ($role === 'admin') {
            $users = (new User())->allBasic();
        }

        $mine = null;
        if ($sellerId) {
            foreach ($items as $it) {
                if ((int)($it['seller_id'] ?? 0) === (int)$sellerId) { $mine = $it; break; }
            }
        }

        $this->render('commissions/new_calc', [
            'title' => 'Novo Cálculo',
            'period' => $period,
            'period_from' => $fromDate,
            'period_to' => $toDate,
            'seller_id' => $sellerId,
            'role' => $role,
            'users' => $users,
            'items' => $items,
            'mine' => $mine,
            'company' => $company,
            'has_snapshot' => (bool)$companySnap,
        ]);
    }
}
