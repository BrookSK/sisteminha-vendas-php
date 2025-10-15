<?php
namespace Controllers;

use Core\Controller;
use Core\Auth;
use Models\TimeOff;

class TimeOffController extends Controller
{
    public function createToday()
    {
        $this->requireRole(['seller','manager','admin']);
        $this->csrfCheck();
        $u = Auth::user();
        $uid = (int)($u['id'] ?? 0);
        $date = $_POST['date'] ?? date('Y-m-d');
        $reason = trim($_POST['reason'] ?? '');
        if ($uid > 0 && $reason !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            (new TimeOff())->create($uid, $date, $reason);
        }
        return $this->redirect('/admin/demands/dashboard');
    }
}
