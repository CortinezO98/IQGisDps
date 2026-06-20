    <?php if (isset($_SESSION[APP_SESSION.'_session_modulos']['Gestión Asistencias']) AND $_SESSION[APP_SESSION.'_session_modulos']['Gestión Asistencias']!=""): ?>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="collapse" href="#gestion-asistencias" aria-expanded="false" aria-controls="gestion-asistencias">
          <i class="menu-icon fas fa-calendar-check"></i>
          <span class="menu-title">Asistencias y Satisfacción</span>
          <i class="menu-arrow fas fa-angle-right"></i>
        </a>
        <div class="collapse" id="gestion-asistencias">
          <ul class="nav flex-column sub-menu">
            <li class="nav-item">
              <a class="nav-link" href="<?php echo URL_MENU; ?>/gestion_asistencias/index.php">
                Sesiones
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="<?php echo URL_MENU; ?>/gestion_asistencias/sesion_crear.php">
                Nueva Sesión
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="<?php echo URL_MENU; ?>/gestion_asistencias/reportes.php">
                Reportes
              </a>
            </li>
          </ul>
        </div>
      </li>
    <?php endif; ?>
