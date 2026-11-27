<template>
  <div class="auth-page">
    <div class="auth-shell">
      <!-- Left Column: App Value Proposition & Benefits -->
      <aside class="auth-aside">
        <div class="auth-brand">
          <span class="auth-brand-name">{{ appName }}</span>
          <span class="auth-brand-tag">Finanzas Personales</span>
        </div>

        <h1 class="auth-lead-title">
          Claridad total sobre tus ingresos, cuentas y compromisos financieros.
        </h1>
        <p class="auth-lead-desc">
          Administra tus movimientos diarios, proyecta tus metas y mantén el control
          de tu patrimonio desde una plataforma ordenada y confiable.
        </p>

        <ul class="auth-benefits">
          <li>
            <span class="benefit-num">01</span>
            <div>
              <strong>Control multicuenta en tiempo real</strong>
              <p>Centraliza efectivo, bancos y billeteras digitales con saldo sincronizado automáticamente en cada operación.</p>
            </div>
          </li>
          <li>
            <span class="benefit-num">02</span>
            <div>
              <strong>Préstamos otorgados y recibidos</strong>
              <p>Gestiona cuotas, intereses, fechas de vencimiento y directorio de prestatarios o acreedores con consulta DNI/RUC.</p>
            </div>
          </li>
          <li>
            <span class="benefit-num">03</span>
            <div>
              <strong>Presupuestos, proyectos y patrimonio</strong>
              <p>Supervisa límites mensuales de gasto, balances por proyecto y el valor actualizado de tus activos.</p>
            </div>
          </li>
          <li>
            <span class="benefit-num">04</span>
            <div>
              <strong>Reportes claros e importación masiva</strong>
              <p>Analiza tu flujo mensual por categoría con desglose detallado e importa movimientos desde Excel o CSV.</p>
            </div>
          </li>
        </ul>

        <div class="auth-aside-footer">
          Información privada, estructurada y disponible cuando la necesites.
        </div>
      </aside>

      <!-- Right Column: Form Slot -->
      <main class="auth-main">
        <div class="auth-form-wrap">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
defineProps({
  appName: { type: String, default: 'myFin' }
})
</script>

<style>
.auth-page {
  min-height: 100vh;
  background-color: #f3f5f8;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 28px 16px;
}

.auth-shell {
  width: 100%;
  max-width: 980px;
  background-color: #ffffff;
  border: 1px solid #dce2ea;
  border-radius: 6px;
  box-shadow: 0 2px 14px rgba(15, 23, 42, 0.05);
  display: flex;
  flex-direction: row;
  overflow: hidden;
}

.auth-aside {
  width: 46%;
  background-color: #172330;
  color: #edf2f7;
  padding: 40px 36px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  border-top: 3px solid #27ae60;
}

.auth-brand {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 18px;
  border-bottom: 1px solid #263647;
  margin-bottom: 24px;
}

.auth-brand-name {
  font-size: 21px;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: #ffffff;
}

.auth-brand-tag {
  font-size: 11.5px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #8fa3b8;
  font-weight: 600;
}

.auth-lead-title {
  margin: 0 0 12px;
  font-size: 20px;
  line-height: 1.35;
  font-weight: 600;
  color: #ffffff;
}

.auth-lead-desc {
  margin: 0 0 24px;
  font-size: 13.5px;
  line-height: 1.6;
  color: #aebecd;
}

.auth-benefits {
  list-style: none;
  margin: 0;
  padding: 0;
}

.auth-benefits li {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 11px 0;
  border-top: 1px solid #233242;
}

.auth-benefits li:last-child {
  border-bottom: 1px solid #233242;
}

.benefit-num {
  font-size: 11px;
  font-weight: 700;
  color: #52c480;
  letter-spacing: 0.04em;
  padding-top: 2px;
  flex-shrink: 0;
}

.auth-benefits strong {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #f8fafc;
  margin-bottom: 2px;
}

.auth-benefits p {
  margin: 0;
  font-size: 12.5px;
  line-height: 1.5;
  color: #9cb0c3;
}

.auth-aside-footer {
  margin-top: 22px;
  font-size: 12px;
  color: #7d92a7;
}

.auth-main {
  width: 54%;
  background-color: #ffffff;
  padding: 44px 42px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.auth-form-wrap {
  width: 100%;
  max-width: 390px;
}

.auth-heading {
  margin: 0 0 6px;
  font-size: 23px;
  font-weight: 700;
  color: #172330;
  letter-spacing: -0.01em;
}

.auth-subheading {
  margin: 0 0 24px;
  font-size: 13.5px;
  color: #64748b;
  line-height: 1.5;
}

.auth-form .form-group {
  margin-bottom: 16px;
}

.auth-form label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 6px;
}

.auth-form .form-control {
  height: 40px;
  border-radius: 4px;
  border: 1px solid #cbd5e1;
  background-color: #ffffff;
  background-image: none;
  box-shadow: none;
  font-size: 14px;
  color: #0f172a;
}

.auth-form .form-control:focus {
  border-color: #172330;
  box-shadow: 0 0 0 2px rgba(23, 35, 48, 0.1);
}

.auth-form .input-group .btn {
  height: 40px;
  border-color: #cbd5e1;
  background-color: #f8fafc;
  background-image: none;
  color: #475569;
  box-shadow: none;
}

.auth-form .input-group .btn:hover {
  background-color: #eef2f6;
  color: #172330;
}

.auth-btn-primary {
  display: block;
  width: 100%;
  height: 42px;
  border: 1px solid #172330;
  border-radius: 4px;
  background-color: #172330;
  background-image: none;
  color: #ffffff;
  font-size: 14px;
  font-weight: 600;
  letter-spacing: 0.01em;
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.auth-btn-primary:hover:not(:disabled),
.auth-btn-primary:focus:not(:disabled) {
  background-color: #243548;
  border-color: #243548;
  color: #ffffff;
}

.auth-btn-primary:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.auth-divider {
  border: 0;
  border-top: 1px solid #e2e8f0;
  margin: 22px 0 16px;
}

.auth-switch {
  text-align: center;
  font-size: 13.5px;
  color: #64748b;
}

.auth-switch a {
  color: #172330;
  font-weight: 600;
  text-decoration: none;
  margin-left: 4px;
}

.auth-switch a:hover {
  text-decoration: underline;
}

.auth-alert {
  padding: 10px 13px;
  border-radius: 4px;
  font-size: 13px;
  line-height: 1.45;
  margin-bottom: 18px;
  background-image: none;
}

.auth-alert-success {
  background-color: #ecfdf5;
  border: 1px solid #a7f3d0;
  color: #065f46;
}

.auth-alert-warning {
  background-color: #fffbeb;
  border: 1px solid #fde68a;
  color: #92400e;
}

@media (max-width: 820px) {
  .auth-shell {
    flex-direction: column-reverse;
  }
  .auth-aside,
  .auth-main {
    width: 100%;
  }
  .auth-main {
    padding: 32px 24px;
  }
  .auth-aside {
    padding: 28px 24px;
  }
}
</style>
