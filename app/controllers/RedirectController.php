<?php
namespace Controllers;

use Core\Controller;

class RedirectController extends Controller
{
    public function salesToInternationalSales()
    {
        $this->redirect('/admin/international-sales');
    }
}