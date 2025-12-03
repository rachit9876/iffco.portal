function showToast(message, type = 'info') {
  let container = document.querySelector('.unver-toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'unver-toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `unver-toast unver-toast-${type}`;
  toast.textContent = message;
  container.appendChild(toast);

  setTimeout(() => {
    toast.classList.add('hiding');
    setTimeout(() => toast.remove(), 300);
  }, 6000);
}
