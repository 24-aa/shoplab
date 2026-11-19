</main>
<footer class="footer">
  <div class="container">星海商城 ShopLab · 教学靶场 · 仅限本地测试</div>
</footer>
<script>
document.querySelectorAll('.alert').forEach(function (el) {
  var delay = el.classList.contains('alert-success') ? 2000 : 4000;

  var close = document.createElement('button');
  close.type = 'button';
  close.className = 'alert-close';
  close.setAttribute('aria-label', '关闭');
  close.textContent = '×';
  el.appendChild(close);

  function dismiss() {
    el.style.transition = 'opacity .3s ease';
    el.style.opacity = '0';
    setTimeout(function () { el.remove(); }, 300);
  }

  var timer = setTimeout(dismiss, delay);
  close.addEventListener('click', function () {
    clearTimeout(timer);
    dismiss();
  });
});
</script>
</body>
</html>
