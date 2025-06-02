<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
include '../functions/db_connect.php';
$admin_id = $_SESSION['admin_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Payment Accounts</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="container py-4">
    <h2 class="mb-4">Payment Accounts</h2>
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addAccountModal">Add Account</button>
    <div id="accountsTableWrapper">
        <!-- Table will be loaded here by AJAX -->
    </div>
</div>
<!-- Add Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addAccountForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Payment Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="account_type" class="form-label">Account Type</label>
          <select class="form-select" name="account_type" id="account_type" required>
            <option value="">Select...</option>
            <option value="gcash">GCash</option>
            <option value="bank">Bank</option>
            <option value="paypal">PayPal</option>
            <option value="credit_card">Credit Card</option>
          </select>
        </div>
        <div class="mb-3" id="accountNumberGroup">
          <label for="account_number" class="form-label">Account Number</label>
          <input type="text" class="form-control" name="account_number" id="account_number">
        </div>
        <div class="mb-3" id="accountEmailGroup" style="display:none;">
          <label for="account_email" class="form-label">Account Email</label>
          <input type="email" class="form-control" name="account_email" id="account_email">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Add</button>
      </div>
    </form>
  </div>
</div>
<!-- Edit Account Modal (populated by JS) -->
<div class="modal fade" id="editAccountModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="editAccountForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Payment Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="account_id" id="edit_account_id">
        <div class="mb-3">
          <label for="edit_account_type" class="form-label">Account Type</label>
          <select class="form-select" name="account_type" id="edit_account_type" required disabled>
            <option value="gcash">GCash</option>
            <option value="bank">Bank</option>
            <option value="paypal">PayPal</option>
            <option value="credit_card">Credit Card</option>
          </select>
        </div>
        <div class="mb-3" id="editAccountNumberGroup">
          <label for="edit_account_number" class="form-label">Account Number</label>
          <input type="text" class="form-control" name="account_number" id="edit_account_number">
        </div>
        <div class="mb-3" id="editAccountEmailGroup" style="display:none;">
          <label for="edit_account_email" class="form-label">Account Email</label>
          <input type="email" class="form-control" name="account_email" id="edit_account_email">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<script>
function loadAccountsTable() {
    $.get('process_admin_payment_account.php', {action: 'list'}, function(data) {
        $('#accountsTableWrapper').html(data);
    });
}
$(function() {
    loadAccountsTable();
    // Show/hide fields based on account type
    $('#account_type').on('change', function() {
        if ($(this).val() === 'paypal') {
            $('#accountNumberGroup').hide();
            $('#accountEmailGroup').show();
        } else {
            $('#accountNumberGroup').show();
            $('#accountEmailGroup').hide();
        }
    });
    $('#addAccountForm').on('submit', function(e) {
        e.preventDefault();
        $.post('process_admin_payment_account.php', $(this).serialize() + '&action=add', function(resp) {
            $('#addAccountModal').modal('hide');
            loadAccountsTable();
        });
    });
    // Edit button click
    $(document).on('click', '.edit-account-btn', function() {
        var row = $(this).closest('tr');
        $('#edit_account_id').val(row.data('id'));
        $('#edit_account_type').val(row.data('type'));
        if (row.data('type') === 'paypal') {
            $('#editAccountNumberGroup').hide();
            $('#editAccountEmailGroup').show();
            $('#edit_account_email').val(row.data('email'));
        } else {
            $('#editAccountNumberGroup').show();
            $('#editAccountEmailGroup').hide();
            $('#edit_account_number').val(row.data('number'));
        }
        $('#editAccountModal').modal('show');
    });
    $('#editAccountForm').on('submit', function(e) {
        e.preventDefault();
        $.post('process_admin_payment_account.php', $(this).serialize() + '&action=edit', function(resp) {
            $('#editAccountModal').modal('hide');
            loadAccountsTable();
        });
    });
    // Delete button click
    $(document).on('click', '.delete-account-btn', function() {
        if (confirm('Are you sure you want to delete this account?')) {
            var id = $(this).closest('tr').data('id');
            $.post('process_admin_payment_account.php', {action: 'delete', account_id: id}, function(resp) {
                loadAccountsTable();
            });
        }
    });
});
</script>
</body>
</html> 