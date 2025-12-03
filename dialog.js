function showDialog(message, onConfirm) {
  const overlay = document.createElement('div');
  overlay.className = 'unver-dialog-overlay show';
  
  overlay.innerHTML = `
    <div class="unver-dialog">
      <div class="unver-dialog-title">Confirm Action</div>
      <div class="unver-dialog-message">${message}</div>
      <div class="unver-dialog-buttons">
        <button class="unver-btn unver-btn-sm" onclick="this.closest('.unver-dialog-overlay').remove()">Cancel</button>
        <button class="unver-btn unver-btn-sm unver-btn-danger" id="confirmBtn">Delete</button>
      </div>
    </div>
  `;
  
  document.body.appendChild(overlay);
  
  document.getElementById('confirmBtn').onclick = () => {
    overlay.remove();
    onConfirm();
  };
  
  overlay.onclick = (e) => {
    if (e.target === overlay) overlay.remove();
  };
}
