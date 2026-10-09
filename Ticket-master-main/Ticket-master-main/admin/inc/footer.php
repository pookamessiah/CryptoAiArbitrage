  <!-- admin/inc/footer.php -->

  <footer style="text-align:center; margin-top:5rem; padding:2rem 0; color:var(--text-muted); font-size:0.95rem; border-top:1px solid var(--border);">
    <p>© <?= date("Y") ?> TICKET MASTER • Admin Panel • All rights reserved</p>
    <p style="margin-top:0.6rem; font-size:0.9rem;">
      Logged in as <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Unknown') ?> 
      • <?= ucfirst($_SESSION['admin_role'] ?? 'Admin') ?>
    </p>
  </footer>
</div>

</body>
</html>
