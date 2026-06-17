<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Instructor Revenue Ledger UI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900">
<div class="mx-auto max-w-7xl p-6" x-data="ledgerApp()" x-init="init()" :dir="direction" :class="direction === 'rtl' ? 'text-right' : 'text-left'">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold" x-text="t('title')"></h1>
        <div class="flex gap-2">
            <button @click="setLanguage('en')" :class="lang === 'en' ? 'bg-slate-800 text-white' : 'bg-white text-slate-700'" class="rounded border px-3 py-2 text-sm font-semibold">English</button>
            <button @click="setLanguage('ar')" :class="lang === 'ar' ? 'bg-slate-800 text-white' : 'bg-white text-slate-700'" class="rounded border px-3 py-2 text-sm font-semibold">العربية</button>
            <a href="/admin" target="_blank" class="rounded bg-slate-700 px-3 py-2 text-sm font-semibold text-white" x-text="t('admin')"></a>
        </div>
    </div>

    <template x-if="flash.message">
        <div :class="flash.type === 'success' ? 'bg-emerald-100 text-emerald-700 border-emerald-300' : 'bg-rose-100 text-rose-700 border-rose-300'" class="mb-4 rounded border px-4 py-3 text-sm">
            <span x-text="flash.message"></span>
        </div>
    </template>

    <div class="mb-6 grid gap-4 lg:grid-cols-2">
        <section class="rounded bg-white p-4 shadow">
            <h2 class="mb-1 text-lg font-semibold" x-text="t('create_step')"></h2>
            <p class="mb-3 text-xs text-slate-500" x-text="t('create_help')"></p>
            <div class="grid gap-3">
                <div>
                    <label class="mb-1 block text-sm font-medium" x-text="t('student_name')"></label>
                    <input x-model="form.student_name" class="w-full rounded border p-2" :placeholder="t('student_placeholder')">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium" x-text="t('plan_type')"></label>
                        <select x-model="form.plan_type" class="w-full rounded border p-2">
                            <option value="monthly" x-text="t('monthly')"></option>
                            <option value="quarterly" x-text="t('quarterly')"></option>
                            <option value="yearly" x-text="t('yearly')"></option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" x-text="t('start_date')"></label>
                        <input x-model="form.starts_at" type="date" class="w-full rounded border p-2">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium" x-text="t('amount_paid')"></label>
                        <input x-model.number="form.amount_paid" type="number" class="w-full rounded border p-2" :placeholder="t('amount_placeholder')">
                        <p class="mt-1 text-xs text-slate-500" x-text="t('amount_help')"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" x-text="t('platform_fee')"></label>
                        <input x-model.number="form.platform_fee_percentage" type="number" class="w-full rounded border p-2" :placeholder="t('fee_placeholder')">
                    </div>
                </div>
                <div class="rounded border p-3">
                    <div class="mb-2 flex items-center justify-between">
                        <p class="font-medium" x-text="t('allocations')"></p>
                        <button @click="addAllocation()" type="button" class="rounded bg-slate-200 px-2 py-1 text-xs" x-text="t('add')"></button>
                    </div>
                    <p class="mb-2 text-xs text-slate-500" x-text="t('allocations_help')"></p>
                    <template x-for="(row, idx) in form.instructors" :key="idx">
                        <div class="mb-2 grid grid-cols-12 gap-2">
                            <div class="col-span-6">
                                <label class="mb-1 block text-xs font-medium" x-text="t('instructor')"></label>
                                <select x-model.number="row.id" class="w-full rounded border p-2">
                                    <option :value="null" x-text="t('choose_instructor')"></option>
                                    <template x-for="instructor in instructors" :key="instructor.id">
                                        <option :value="instructor.id" x-text="instructor.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="col-span-4">
                                <label class="mb-1 block text-xs font-medium" x-text="t('allocation_percent')"></label>
                                <input x-model.number="row.allocation_percentage" type="number" class="w-full rounded border p-2" :placeholder="t('allocation_placeholder')">
                            </div>
                            <button @click="removeAllocation(idx)" type="button" class="col-span-2 rounded bg-rose-100 text-rose-700">X</button>
                        </div>
                    </template>
                </div>
                <button @click="createSubscription()" :disabled="loading" class="rounded bg-emerald-600 px-3 py-2 font-semibold text-white disabled:opacity-50" x-text="t('create_subscription')"></button>
            </div>
        </section>

        <section class="rounded bg-white p-4 shadow">
            <h2 class="mb-1 text-lg font-semibold" x-text="t('operations_step')"></h2>
            <p class="mb-3 text-xs text-slate-500" x-text="t('operations_help')"></p>
            <div class="grid gap-3">
                <div>
                    <label class="mb-1 block text-sm font-medium" x-text="t('accrual_date')"></label>
                    <input x-model="accrualDate" type="date" class="w-full rounded border p-2">
                </div>
                <button @click="accrueRevenue()" :disabled="loading" class="rounded bg-blue-600 px-3 py-2 font-semibold text-white disabled:opacity-50" x-text="t('run_accrual')"></button>

                <div>
                    <label class="mb-1 block text-sm font-medium" x-text="t('batch_key')"></label>
                    <input x-model="batchKey" class="w-full rounded border p-2" :placeholder="t('batch_placeholder')">
                </div>
                <button @click="processPayouts()" :disabled="loading" class="rounded bg-violet-600 px-3 py-2 font-semibold text-white disabled:opacity-50" x-text="t('process_payouts')"></button>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium" x-text="t('subscription_id')"></label>
                        <input x-model.number="refund.subscription_id" type="number" class="w-full rounded border p-2" :placeholder="t('subscription_id_placeholder')">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium" x-text="t('refund_date')"></label>
                        <input x-model="refund.refunded_at" type="date" class="w-full rounded border p-2">
                    </div>
                    <button @click="refundSubscription()" :disabled="loading" class="rounded bg-amber-600 px-3 py-2 font-semibold text-white disabled:opacity-50" x-text="t('refund')"></button>
                </div>
            </div>
        </section>
    </div>

    <section class="mb-6 rounded bg-white p-4 shadow">
        <h2 class="mb-3 text-lg font-semibold" x-text="t('instructors_balances')"></h2>
        <div class="mb-3">
            <button @click="loadInstructors()" class="rounded bg-slate-800 px-3 py-2 text-sm font-semibold text-white" x-text="t('refresh')"></button>
        </div>
        <div class="overflow-auto">
            <table class="w-full text-sm">
                <thead>
                <tr class="border-b" :class="direction === 'rtl' ? 'text-right' : 'text-left'">
                    <th class="p-2" x-text="t('id')"></th>
                    <th class="p-2" x-text="t('name')"></th>
                    <th class="p-2" x-text="t('outstanding')"></th>
                    <th class="p-2" x-text="t('earned')"></th>
                    <th class="p-2" x-text="t('paid')"></th>
                </tr>
                </thead>
                <tbody>
                <template x-for="instructor in instructors" :key="instructor.id">
                    <tr class="border-b">
                        <td class="p-2" x-text="instructor.id"></td>
                        <td class="p-2" x-text="instructor.name"></td>
                        <td class="p-2" x-text="instructor.balance?.outstanding ?? '-'"></td>
                        <td class="p-2" x-text="instructor.balance?.total_earned ?? '-'"></td>
                        <td class="p-2" x-text="instructor.balance?.total_paid ?? '-'"></td>
                    </tr>
                </template>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    function ledgerApp() {
        return {
            loading: false,
            lang: 'en',
            direction: 'ltr',
            instructors: [],
            flash: { message: '', type: 'success' },
            accrualDate: new Date().toISOString().slice(0, 10),
            batchKey: '',
            refund: { subscription_id: null, refunded_at: new Date().toISOString().slice(0, 10) },
            form: {
                student_name: '',
                plan_type: 'monthly',
                amount_paid: 30000,
                platform_fee_percentage: 20,
                starts_at: new Date().toISOString().slice(0, 10),
                instructors: [{ id: null, allocation_percentage: 100 }],
            },
            i18n: {
                en: {
                    title: 'Instructor Revenue Ledger',
                    admin: 'Admin',
                    create_step: 'Step 1: Create Subscription',
                    create_help: 'Fill all fields in order. Money values are in cents (piastres).',
                    student_name: 'Student name',
                    student_placeholder: 'e.g. Mohamed Ali',
                    plan_type: 'Plan type',
                    monthly: 'Monthly (30 days)',
                    quarterly: 'Quarterly (90 days)',
                    yearly: 'Yearly (365 days)',
                    start_date: 'Start date',
                    amount_paid: 'Amount paid (cents)',
                    amount_placeholder: 'e.g. 30000',
                    amount_help: 'Example: 30000 = 300.00 EGP',
                    platform_fee: 'Platform fee (%)',
                    fee_placeholder: 'e.g. 20',
                    allocations: 'Instructor allocations',
                    allocations_help: 'Choose instructor and percentage. Total percentages must equal 100.',
                    add: 'Add',
                    instructor: 'Instructor',
                    choose_instructor: 'Choose instructor',
                    allocation_percent: 'Allocation %',
                    allocation_placeholder: 'e.g. 100',
                    create_subscription: 'Create subscription',
                    operations_step: 'Step 2 / 3 / 4: Operations',
                    operations_help: 'Use actions in order: Accrual, then Payout. Refund is optional.',
                    accrual_date: 'Accrual as-of date',
                    run_accrual: 'Run revenue accrual',
                    batch_key: 'Batch key (optional)',
                    batch_placeholder: 'e.g. payout-2026-06-16',
                    process_payouts: 'Process payouts (sync)',
                    subscription_id: 'Subscription ID',
                    subscription_id_placeholder: 'e.g. 1',
                    refund_date: 'Refund date',
                    refund: 'Refund',
                    instructors_balances: 'Instructors balances',
                    refresh: 'Refresh',
                    id: 'ID',
                    name: 'Name',
                    outstanding: 'Outstanding',
                    earned: 'Earned',
                    paid: 'Paid',
                    success_create: 'Subscription created successfully.',
                    success_accrual: 'Revenue accrual completed successfully.',
                    success_payout: 'Payout process completed successfully.',
                    success_refund: 'Refund processed successfully.',
                    error_generic: 'Something went wrong. Please check your input.',
                },
                ar: {
                    title: 'دفتر إيرادات المدربين',
                    admin: 'لوحة الإدارة',
                    create_step: 'الخطوة 1: إنشاء اشتراك',
                    create_help: 'املأ كل الحقول بالترتيب. القيم المالية هنا بالقروش.',
                    student_name: 'اسم الطالب',
                    student_placeholder: 'مثال: محمد علي',
                    plan_type: 'نوع الباقة',
                    monthly: 'شهري (30 يوم)',
                    quarterly: 'ربع سنوي (90 يوم)',
                    yearly: 'سنوي (365 يوم)',
                    start_date: 'تاريخ البداية',
                    amount_paid: 'المبلغ المدفوع (قروش)',
                    amount_placeholder: 'مثال: 30000',
                    amount_help: 'مثال: 30000 = 300.00 جنيه',
                    platform_fee: 'نسبة المنصة (%)',
                    fee_placeholder: 'مثال: 20',
                    allocations: 'توزيع المدربين',
                    allocations_help: 'اختَر المدرب ثم نسبة التوزيع. مجموع النسب يجب أن يساوي 100.',
                    add: 'إضافة',
                    instructor: 'المدرب',
                    choose_instructor: 'اختر المدرب',
                    allocation_percent: 'نسبة التوزيع %',
                    allocation_placeholder: 'مثال: 100',
                    create_subscription: 'إنشاء الاشتراك',
                    operations_step: 'الخطوة 2 / 3 / 4: العمليات',
                    operations_help: 'نفّذ العمليات بالترتيب: احتساب الإيراد ثم الدفع. الاسترداد اختياري.',
                    accrual_date: 'تاريخ احتساب الإيراد',
                    run_accrual: 'تشغيل احتساب الإيراد',
                    batch_key: 'مفتاح الدفعة (اختياري)',
                    batch_placeholder: 'مثال: payout-2026-06-16',
                    process_payouts: 'تشغيل المدفوعات (فوري)',
                    subscription_id: 'رقم الاشتراك',
                    subscription_id_placeholder: 'مثال: 1',
                    refund_date: 'تاريخ الاسترداد',
                    refund: 'استرداد',
                    instructors_balances: 'أرصدة المدربين',
                    refresh: 'تحديث',
                    id: 'الرقم',
                    name: 'الاسم',
                    outstanding: 'المتبقي',
                    earned: 'المكتسب',
                    paid: 'المدفوع',
                    success_create: 'تم إنشاء الاشتراك بنجاح.',
                    success_accrual: 'تم احتساب الإيراد بنجاح.',
                    success_payout: 'تم تنفيذ المدفوعات بنجاح.',
                    success_refund: 'تم تنفيذ الاسترداد بنجاح.',
                    error_generic: 'حدث خطأ. يرجى مراجعة البيانات المدخلة.',
                },
            },

            t(key) {
                return this.i18n[this.lang][key] ?? key;
            },

            setLanguage(lang) {
                this.lang = lang;
                this.direction = lang === 'ar' ? 'rtl' : 'ltr';
            },

            notify(message, type = 'success') {
                this.flash = { message, type };
                setTimeout(() => {
                    this.flash = { message: '', type: 'success' };
                }, 3000);
            },

            async init() {
                this.setLanguage('en');
                await this.loadInstructors();
            },

            addAllocation() {
                this.form.instructors.push({ id: null, allocation_percentage: 0 });
            },

            removeAllocation(index) {
                if (this.form.instructors.length === 1) return;
                this.form.instructors.splice(index, 1);
            },

            async request(url, method = 'GET', body = null) {
                this.loading = true;
                try {
                    const response = await fetch(url, {
                        method,
                        headers: { 'Content-Type': 'application/json' },
                        body: body ? JSON.stringify(body) : null,
                    });
                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || this.t('error_generic'));
                    }
                    return data;
                } finally {
                    this.loading = false;
                }
            },

            async loadInstructors() {
                const data = await this.request('/api/instructors');
                this.instructors = data.data || [];
            },

            async createSubscription() {
                try {
                    await this.request('/api/subscriptions', 'POST', this.form);
                    this.notify(this.t('success_create'));
                    await this.loadInstructors();
                } catch (error) {
                    this.notify(error.message, 'error');
                }
            },

            async accrueRevenue() {
                try {
                    await this.request('/api/revenue/accrue', 'POST', { as_of: this.accrualDate });
                    this.notify(this.t('success_accrual'));
                    await this.loadInstructors();
                } catch (error) {
                    this.notify(error.message, 'error');
                }
            },

            async processPayouts() {
                try {
                    await this.request('/api/payouts/process', 'POST', {
                        batch_key: this.batchKey || undefined,
                        sync: true,
                    });
                    this.notify(this.t('success_payout'));
                    await this.loadInstructors();
                } catch (error) {
                    this.notify(error.message, 'error');
                }
            },

            async refundSubscription() {
                if (!this.refund.subscription_id) return;
                try {
                    await this.request(`/api/subscriptions/${this.refund.subscription_id}/refund`, 'POST', {
                        refunded_at: this.refund.refunded_at,
                    });
                    this.notify(this.t('success_refund'));
                    await this.loadInstructors();
                } catch (error) {
                    this.notify(error.message, 'error');
                }
            },
        };
    }
</script>
</body>
</html>
