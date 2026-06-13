<?php

namespace App\Enums;

enum Permission: string
{
    case ClientsView = 'clients.view';
    case ClientsCreate = 'clients.create';
    case ClientsUpdate = 'clients.update';
    case ClientsDelete = 'clients.delete';

    case ProductsView = 'products.view';
    case ProductsCreate = 'products.create';
    case ProductsUpdate = 'products.update';
    case ProductsDelete = 'products.delete';

    case InvoicesView = 'invoices.view';
    case InvoicesViewAll = 'invoices.view-all';
    case InvoicesCreate = 'invoices.create';
    case InvoicesCancel = 'invoices.cancel';

    case ReportsView = 'reports.view';
    case ReportsViewAll = 'reports.view-all';

    case UsersManage = 'users.manage';
    case CompanyManage = 'company.manage';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function forVendedor(): array
    {
        return [
            self::ClientsView,
            self::ClientsCreate,
            self::ProductsView,
            self::InvoicesView,
            self::InvoicesCreate,
            self::ReportsView,
        ];
    }
}
