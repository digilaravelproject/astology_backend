@extends('admin.layouts.app')

@section('content')
<div>
    <!-- Page Header -->
    <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6 border-b border-gray-lighter pb-8">
        <div>
            <a href="{{ route('admin.astrologer-bank-accounts.index') }}" class="inline-flex items-center gap-2 text-[10px] font-black text-primary uppercase tracking-widest mb-4 hover:gap-4 transition-all group">
                <i class="fas fa-arrow-left"></i> Back to Bank Accounts
            </a>
            <h1 class="text-3xl md:text-4xl font-black text-dark tracking-tighter uppercase">{{ isset($account) && $account->id ? 'Edit' : 'New' }} Bank Account</h1>
            <p class="text-sm text-gray font-medium mt-2 italic">Manage astrologer bank account information</p>
        </div>
        <div class="flex gap-4">
            <button form="accountForm" type="submit" class="px-10 py-4 bg-dark text-white text-[11px] font-black uppercase rounded-2xl hover:bg-black transition-all shadow-xl shadow-dark/20 flex items-center gap-3">
                <i class="fas fa-save"></i> {{ isset($account) && $account->id ? 'Update' : 'Create' }} Account
            </button>
        </div>
    </div>

    <!-- Form -->
    <form id="accountForm" action="{{ isset($account) && $account->id ? route('admin.astrologer-bank-accounts.update', $account->id) : route('admin.astrologer-bank-accounts.store') }}" method="POST" enctype="multipart/form-data" class="max-w-[1000px]">
        @csrf
        @if(isset($account) && $account->id)
            @method('PUT')
        @endif

        <div class="space-y-10">
            <!-- Section 1: Account Holder -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-[100px]"></div>
                <h2 class="text-[10px] font-black text-primary uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-primary/30"></span> 01. Account Holder
                </h2>
                
                <div class="space-y-8">
                    <div class="group">
                        <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3 group-focus-within:text-dark transition-colors">Astrologer</label>
                        <select name="astrologer_id" class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-black text-dark focus:bg-white focus:border-primary/20 focus:ring-0 transition-all" required>
                            <option value="">Select Astrologer</option>
                            @foreach($astrologers as $astrologer)
                                <option value="{{ $astrologer->astrologer->id ?? '' }}" {{ old('astrologer_id', $account->astrologer_id ?? '') == ($astrologer->astrologer->id ?? '') ? 'selected' : '' }}>
                                    {{ $astrologer->name }} ({{ $astrologer->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('astrologer_id') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="group">
                        <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Account Holder Name</label>
                        <input type="text" name="account_holder_name" value="{{ old('account_holder_name', $account->account_holder_name ?? '') }}" placeholder="Full name as per bank records..." class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-bold text-dark placeholder:text-gray-light focus:bg-white focus:border-primary/20 focus:ring-0 transition-all" required>
                        @error('account_holder_name') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Section 2: Bank Details -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-info uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-info/30"></span> 02. Bank Information
                </h2>
                
                <div class="space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="group">
                            <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Bank Name</label>
                            <input type="text" name="bank_name" value="{{ old('bank_name', $account->bank_name ?? '') }}" placeholder="e.g. HDFC Bank" class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-bold text-dark placeholder:text-gray-light focus:bg-white focus:border-info/20 focus:ring-0 transition-all" required>
                            @error('bank_name') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="group">
                            <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">IFSC Code</label>
                            <input type="text" name="ifsc_code" value="{{ old('ifsc_code', $account->ifsc_code ?? '') }}" placeholder="e.g. HDFC0001234" class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-bold text-dark placeholder:text-gray-light focus:bg-white focus:border-info/20 focus:ring-0 transition-all" required>
                            @error('ifsc_code') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="group">
                        <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Account Number</label>
                        <input type="text" name="account_number" value="{{ old('account_number', $account->account_number ?? '') }}" placeholder="Enter complete account number" class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-bold text-dark placeholder:text-gray-light focus:bg-white focus:border-info/20 focus:ring-0 transition-all" required>
                        @error('account_number') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="group">
                            <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Account Type</label>
                            <select name="account_type" class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-bold text-dark focus:bg-white focus:border-info/20 focus:ring-0 transition-all" required>
                                <option value="">Select Type</option>
                                <option value="savings" {{ old('account_type', $account->account_type ?? '') === 'savings' ? 'selected' : '' }}>Savings Account</option>
                                <option value="current" {{ old('account_type', $account->account_type ?? '') === 'current' ? 'selected' : '' }}>Current Account</option>
                                <option value="business" {{ old('account_type', $account->account_type ?? '') === 'business' ? 'selected' : '' }}>Business Account</option>
                            </select>
                            @error('account_type') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="group">
                            <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Branch Name</label>
                            <input type="text" name="branch_name" value="{{ old('branch_name', $account->branch_name ?? '') }}" placeholder="Bank branch name..." class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-bold text-dark placeholder:text-gray-light focus:bg-white focus:border-info/20 focus:ring-0 transition-all">
                            @error('branch_name') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Document Upload -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-success uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-success/30"></span> 03. Verification Documents
                </h2>
                
                <div class="space-y-8">
                    <div class="group">
                        <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Passbook/Bank Statement</label>
                        <div class="relative group/upload">
                            <input type="file" name="passbook_document" accept=".pdf,.jpg,.jpeg,.png" class="hidden" id="passbook_upload">
                            <label for="passbook_upload" class="block aspect-video bg-light rounded-[32px] overflow-hidden border-2 border-dashed border-gray-lighter hover:border-success transition-all cursor-pointer">
                                <div class="absolute inset-0 flex flex-col items-center justify-center p-6 text-center z-10">
                                    <div class="w-16 h-16 bg-white shadow-lg rounded-2xl flex items-center justify-center text-success text-xl mb-4 group-hover/upload:scale-110 transition-all">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <span class="text-[10px] font-black text-dark uppercase tracking-widest">Upload Document</span>
                                    <span class="text-[9px] text-gray mt-2 italic font-medium">PDF, JPG, PNG (Max 5MB)</span>
                                </div>
                                <div class="absolute inset-0 bg-dark/5 opacity-0 group-hover/upload:opacity-100 transition-all"></div>
                            </label>
                            @if(isset($account) && $account->passbook_document)
                            <div class="mt-3 p-3 bg-success/10 border border-success/20 rounded-2xl text-success text-[10px] font-bold">
                                <i class="fas fa-check-circle mr-2"></i> Document uploaded
                            </div>
                            @endif
                        </div>
                        @error('passbook_document') <span class="text-xs text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="group">
                            <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Document Type</label>
                            <select name="document_type" class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-bold text-dark focus:bg-white focus:border-success/20 focus:ring-0 transition-all">
                                <option value="">Select Type</option>
                                <option value="passbook" {{ old('document_type', $account->document_type ?? '') === 'passbook' ? 'selected' : '' }}>Passbook</option>
                                <option value="statement" {{ old('document_type', $account->document_type ?? '') === 'statement' ? 'selected' : '' }}>Bank Statement</option>
                                <option value="cheque" {{ old('document_type', $account->document_type ?? '') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                                <option value="other" {{ old('document_type', $account->document_type ?? '') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div class="group">
                            <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Verification Status</label>
                            <select name="is_active" class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-bold text-dark focus:bg-white focus:border-success/20 focus:ring-0 transition-all">
                                <option value="0" {{ old('is_active', $account->is_active ?? 0) == 0 ? 'selected' : '' }}>Pending Verification</option>
                                <option value="1" {{ old('is_active', $account->is_active ?? 0) == 1 ? 'selected' : '' }}>Verified</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Notes -->
            <div class="bg-white p-10 rounded-[48px] border border-gray-lighter shadow-sm">
                <h2 class="text-[10px] font-black text-warning uppercase tracking-[0.3em] mb-8 flex items-center gap-3">
                    <span class="w-8 h-px bg-warning/30"></span> 04. Internal Notes
                </h2>
                
                <div>
                    <label class="block text-[10px] font-black text-gray uppercase tracking-widest mb-3">Notes</label>
                    <textarea name="notes" rows="6" placeholder="Add any internal notes or verification details..." class="w-full bg-light/30 border-2 border-transparent px-6 py-5 rounded-[24px] text-sm font-medium text-dark placeholder:text-gray-light focus:bg-white focus:border-warning/20 focus:ring-0 transition-all resize-none">{{ old('notes', $account->notes ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
