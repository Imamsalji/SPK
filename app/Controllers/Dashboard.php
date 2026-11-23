<?php

namespace App\Controllers;

use App\Models\BrandModel;
use App\Models\CalculationModel;
use App\Models\CriterionModel;
use App\Models\LaptopModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $user         = auth_user();
        $calculations = model(CalculationModel::class);

        if (! auth_is('admin')) {
            $calculations->where('user_id', $user['id']);
        }

        return view('dashboard/index', [
            'title' => 'Dashboard',
            'user'  => $user,
            'stats' => [
                'laptops'      => model(LaptopModel::class)->where('is_active', 1)->countAllResults(),
                'brands'       => model(BrandModel::class)->countAllResults(),
                'criteria'     => model(CriterionModel::class)->where('is_active', 1)->countAllResults(),
                'calculations' => $calculations->countAllResults(),
            ],
        ]);
    }
}
