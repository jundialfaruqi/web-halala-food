<?php

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.admin'), Title('Pengaturan Usaha - Halala Food')] class extends Component
{
    public string $company_name = '';
    public ?string $legal_name = '';
    public ?string $tagline = '';
    public ?string $phone = '';
    public ?string $email = '';
    public ?string $address = '';

    /**
     * @var array<int, array{bank_name: string, account_number: string, account_name: string}>
     */
    public array $bank_accounts = [];

    public ?string $invoice_notes = '';
    public ?string $delivery_notes = '';

    public function mount()
    {
        if (Gate::denies('pengaturan-view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat pengaturan usaha.');
        }

        $settings = BusinessSetting::getSettings();

        $this->company_name = $settings->company_name ?? 'HALALA FOOD';
        $this->legal_name = $settings->legal_name ?? '';
        $this->tagline = $settings->tagline ?? '';
        $this->phone = $settings->phone ?? '';
        $this->email = $settings->email ?? '';
        $this->address = $settings->address ?? '';
        $this->bank_accounts = is_array($settings->bank_accounts) ? $settings->bank_accounts : [];
        $this->invoice_notes = $settings->invoice_notes ?? '';
        $this->delivery_notes = $settings->delivery_notes ?? '';

        if (empty($this->bank_accounts)) {
            $this->addBankAccount();
        }
    }

    public function addBankAccount(): void
    {
        $this->bank_accounts[] = [
            'bank_name' => '',
            'account_number' => '',
            'account_name' => '',
        ];
    }

    public function removeBankAccount(int $index): void
    {
        if (isset($this->bank_accounts[$index])) {
            unset($this->bank_accounts[$index]);
            $this->bank_accounts = array_values($this->bank_accounts);
        }
    }

    public function save()
    {
        if (Gate::denies('pengaturan-edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah pengaturan usaha.');
        }

        $this->validate([
            'company_name' => ['required', 'string', 'max:100'],
            'legal_name' => ['nullable', 'string', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'bank_accounts' => ['nullable', 'array'],
            'bank_accounts.*.bank_name' => ['required_with:bank_accounts.*.account_number', 'nullable', 'string', 'max:50'],
            'bank_accounts.*.account_number' => ['required_with:bank_accounts.*.bank_name', 'nullable', 'string', 'max:50'],
            'bank_accounts.*.account_name' => ['required_with:bank_accounts.*.bank_name', 'nullable', 'string', 'max:100'],
            'invoice_notes' => ['nullable', 'string', 'max:1000'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'company_name.required' => 'Nama merk / nama usaha wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'bank_accounts.*.bank_name.required_with' => 'Nama bank wajib diisi jika nomor rekening diisi.',
            'bank_accounts.*.account_number.required_with' => 'Nomor rekening wajib diisi.',
            'bank_accounts.*.account_name.required_with' => 'Nama atas nama rekening wajib diisi.',
        ]);

        // Filter out completely blank bank account entries
        $cleanedBankAccounts = [];
        foreach ($this->bank_accounts as $account) {
            $bankName = trim($account['bank_name'] ?? '');
            $accNum = trim($account['account_number'] ?? '');
            $accName = trim($account['account_name'] ?? '');

            if ($bankName !== '' || $accNum !== '' || $accName !== '') {
                $cleanedBankAccounts[] = [
                    'bank_name' => $bankName,
                    'account_number' => $accNum,
                    'account_name' => $accName,
                ];
            }
        }

        $settings = BusinessSetting::getSettings();
        $settings->update([
            'company_name' => $this->company_name,
            'legal_name' => $this->legal_name,
            'tagline' => $this->tagline,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'bank_accounts' => $cleanedBankAccounts,
            'invoice_notes' => $this->invoice_notes,
            'delivery_notes' => $this->delivery_notes,
        ]);

        $this->bank_accounts = $cleanedBankAccounts;
        if (empty($this->bank_accounts)) {
            $this->addBankAccount();
        }

        session()->flash('toast', [
            'message' => 'Pengaturan profil usaha dan rekening pembayaran berhasil disimpan.',
            'type' => 'success',
        ]);
    }
};
