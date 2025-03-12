<?php
session_start();
session_unset();
session_destroy();
echo "<script>alert('Logout realizado com sucesso, até mais!'); window.location.href='login.html';</script>";
exit();
?>