<?php

namespace Database\Seeders;

use App\Models\AdjustmentItem;
use App\Models\DispatchSheet;
use App\Models\DispatchSheetItem;
use App\Models\Godown;
use App\Models\Grn;
use App\Models\GrnItem;
use App\Models\Setting;
use App\Models\Sku;
use App\Models\StockAdjustment;
use App\Models\StockRecord;
use App\Models\User;
use App\Services\NumberGenerator;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $stockService = app(StockService::class);

        // Settings
        Setting::set('company_name', 'Taher Brothers', null);
        Setting::set('company_logo', 'logo/taher-brothers-logo.png', null);
        Setting::set('company_address', 'Century Plaza, 81, Netaji Subhas Road, Kolkata - 700 001, West Bengal', null);
        Setting::set('company_phone', '+91 33 2243 2105, 2243, 2727', null);
        Setting::set('company_fax', '91-33-2242 3798', null);
        Setting::set('company_email', 'taherco@hotmail.com', null);
        Setting::set('default_low_stock_threshold', '10', null);

        // Godowns
        $godowns = [
            ['code' => 'GD-01', 'name' => 'Howrah', 'address' => 'Howrah, West Bengal', 'contact_phone' => '+919876543001'],
            ['code' => 'GD-02', 'name' => 'Domjur 1', 'address' => 'Domjur, West Bengal', 'contact_phone' => '+919876543002'],
            ['code' => 'GD-03', 'name' => 'Domjur 2', 'address' => 'Domjur, West Bengal', 'contact_phone' => '+919876543003'],
        ];
        foreach ($godowns as $data) {
            Godown::create($data);
        }

        // Single admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@company.com',
            'password' => Hash::make('Admin@123'),
            'is_active' => true,
            'must_change_password' => false,
        ]);

        // Some StockService methods stamp the ledger's performed_by from auth()->id() —
        // log in as the seeded admin so those entries attribute correctly.
        auth()->loginUsingId($admin->id);

        // SKUs — imported from GHUSURY INDEX.xlsx (137 catalog items + 15 stock-take items)
        $skuData = require database_path('seeders/data/skus.php');
        $mainGodown = Godown::where('code', 'GD-01')->first();
        $realStockItems = [];

        foreach ($skuData as $data) {
            $initialQty = $data['initial_qty'];
            unset($data['initial_qty']);

            $sku = Sku::create($data);

            foreach (Godown::all() as $godown) {
                StockRecord::create([
                    'sku_id' => $sku->id,
                    'godown_id' => $godown->id,
                    'on_hand' => 0,
                    'reserved' => 0,
                ]);
            }

            if ($initialQty > 0) {
                $realStockItems[] = ['sku_id' => $sku->id, 'quantity' => $initialQty];
            }
        }

        // Initial Stock Load via Adjustment — real quantities from the stock-take, loaded into Main Warehouse
        DB::transaction(function () use ($mainGodown, $stockService, $realStockItems, $admin) {
            $adjustment = StockAdjustment::create([
                'adjustment_number' => NumberGenerator::adjustment(),
                'godown_id' => $mainGodown->id,
                'reason' => 'initial_load',
                'reason_notes' => 'Initial stock load from physical stock-take',
                'created_by' => $admin->id,
            ]);

            foreach ($realStockItems as $item) {
                AdjustmentItem::create([
                    'stock_adjustment_id' => $adjustment->id,
                    'sku_id' => $item['sku_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            $adjustment->load(['items.sku', 'godown']);
            $stockService->processAdjustment($adjustment);
        });

        // Sample GRNs (one per godown)
        foreach (Godown::all() as $godown) {
            DB::transaction(function () use ($godown, $stockService, $admin) {
                $grn = Grn::create([
                    'grn_number' => NumberGenerator::grn(),
                    'godown_id' => $godown->id,
                    'receipt_date' => today(),
                    'challan_no' => 'CH-' . rand(1000, 9999),
                    'supplier_name' => 'Test Supplier ' . $godown->id,
                    'created_by' => $admin->id,
                ]);

                $skus = Sku::inRandomOrder()->limit(3)->get();
                foreach ($skus as $sku) {
                    GrnItem::create([
                        'grn_id' => $grn->id,
                        'sku_id' => $sku->id,
                        'quantity' => rand(20, 100),
                    ]);
                }

                $grn->load('items');
                $stockService->processStockIn($grn);
            });
        }

        // Sample Dispatch Sheets

        // Pending dispatch sheet
        DB::transaction(function () use ($admin, $stockService) {
            $sheet = DispatchSheet::create([
                'ds_number' => NumberGenerator::dispatchSheet(),
                'godown_id' => 1,
                'status' => 'pending',
                'created_by' => $admin->id,
                'customer_name' => 'ABC Traders',
                'delivery_address' => '123 Main St, Kolkata',
                'delivery_date' => today()->addDays(3),
                'vehicle_no' => 'WB-02-AB-1234',
                'driver_name' => 'Ramesh',
                'driver_phone' => '+919876543210',
            ]);

            $items = ['GIP-085' => 40, 'MSB-060' => 25, 'GIP-087' => 20];
            foreach ($items as $code => $qty) {
                DispatchSheetItem::create([
                    'dispatch_sheet_id' => $sheet->id,
                    'sku_id' => Sku::where('code', $code)->first()->id,
                    'quantity' => $qty,
                ]);
            }

            $sheet->load(['items.sku', 'godown']);
            $stockService->reserveStock($sheet);
        });

        // Dispatched sheet
        DB::transaction(function () use ($admin, $stockService) {
            $sheet = DispatchSheet::create([
                'ds_number' => NumberGenerator::dispatchSheet(),
                'godown_id' => 1,
                'status' => 'pending',
                'created_by' => $admin->id,
                'customer_name' => 'XYZ Industries',
                'delivery_date' => today(),
            ]);

            $items = ['MSB-056' => 15, 'PVC-001' => 10];
            foreach ($items as $code => $qty) {
                DispatchSheetItem::create([
                    'dispatch_sheet_id' => $sheet->id,
                    'sku_id' => Sku::where('code', $code)->first()->id,
                    'quantity' => $qty,
                ]);
            }

            $sheet->load(['items.sku', 'godown']);
            $stockService->reserveStock($sheet);

            // Now dispatch it
            $stockService->processDispatch($sheet);
            $sheet->update([
                'status' => 'dispatched',
                'dispatched_at' => now(),
                'dispatched_by' => $admin->id,
            ]);
        });

        // Cancelled sheet
        DB::transaction(function () use ($admin, $stockService) {
            $sheet = DispatchSheet::create([
                'ds_number' => NumberGenerator::dispatchSheet(),
                'godown_id' => 1,
                'status' => 'pending',
                'created_by' => $admin->id,
                'customer_name' => 'Test Customer',
            ]);

            $sku = Sku::where('code', 'MSB-062')->first();
            DispatchSheetItem::create([
                'dispatch_sheet_id' => $sheet->id,
                'sku_id' => $sku->id,
                'quantity' => 8,
            ]);

            $sheet->load(['items.sku', 'godown']);
            $stockService->reserveStock($sheet);

            // Cancel it
            $stockService->releaseStock($sheet);
            $sheet->update([
                'status' => 'cancelled',
                'cancel_reason' => 'Test cancellation',
                'cancelled_at' => now(),
            ]);
        });

        // Sample completed stock transfer
        DB::transaction(function () use ($admin, $stockService) {
            $transfer = \App\Models\StockTransfer::create([
                'transfer_number' => NumberGenerator::transfer(),
                'source_godown_id' => 1,
                'dest_godown_id' => 3,
                'status' => 'pending',
                'created_by' => $admin->id,
                'notes' => 'Test transfer',
            ]);

            $items = ['PVC-002' => 10, 'GIP-085' => 50];
            foreach ($items as $code => $qty) {
                \App\Models\TransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'sku_id' => Sku::where('code', $code)->first()->id,
                    'quantity' => $qty,
                ]);
            }

            $transfer->load(['items.sku', 'sourceGodown']);
            $stockService->processTransferOut($transfer);

            // Accept it
            $transfer->load(['items.sku']);
            $stockService->processTransferAccept($transfer);
            $transfer->update([
                'status' => 'completed',
                'resolved_by' => $admin->id,
                'resolved_at' => now(),
            ]);
        });

        $this->command->info('Test data seeded successfully!');
        $this->command->info('Admin: admin@company.com / Admin@123');
    }
}
