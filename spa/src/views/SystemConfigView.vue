<template>
  <div>
    <ol class="breadcrumb">
      <li><a href="#" @click.prevent="$router.push('/dashboard')">Inicio</a></li>
      <li class="active">Configuración General del Sistema (Admin)</li>
    </ol>

    <!-- Environment Summary Cards -->
    <div class="row" style="margin-bottom:20px;">
      <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="panel panel-info">
          <div class="panel-heading"><i class="glyphicon glyphicon-hdd"></i> Base de Datos</div>
          <div class="panel-body text-center">
            <h4 style="margin:0 0 4px;font-weight:700;" class="text-info">{{ envInfo.db_name || 'personal_finance' }}</h4>
            <small class="text-muted">{{ envInfo.db_version || 'MariaDB / MySQL' }}</small>
          </div>
        </div>
      </div>

      <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="panel panel-success">
          <div class="panel-heading"><i class="glyphicon glyphicon-th-list"></i> Tablas y Vistas</div>
          <div class="panel-body text-center">
            <h4 style="margin:0 0 4px;font-weight:700;" class="text-success">
              {{ envInfo.tables_count }} Tablas · {{ envInfo.views_count }} Vistas
            </h4>
            <small class="text-muted">objetos estructurales activos</small>
          </div>
        </div>
      </div>

      <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="panel panel-warning">
          <div class="panel-heading"><i class="glyphicon glyphicon-flash"></i> Rutinas y Triggers</div>
          <div class="panel-body text-center">
            <h4 style="margin:0 0 4px;font-weight:700;" class="text-warning">
              {{ envInfo.procedures_count }} SP · {{ envInfo.triggers_count }} Triggers
            </h4>
            <small class="text-muted">automatización en motor BD</small>
          </div>
        </div>
      </div>

      <div class="col-xs-12 col-sm-6 col-md-3">
        <div class="panel" :class="pendingCount > 0 ? 'panel-danger' : 'panel-primary'">
          <div class="panel-heading"><i class="glyphicon glyphicon-transfer"></i> Migraciones</div>
          <div class="panel-body text-center">
            <h4 style="margin:0 0 4px;font-weight:700;" :class="pendingCount > 0 ? 'text-danger' : 'text-primary'">
              {{ appliedCount }} Aplicadas · {{ pendingCount }} Pend.
            </h4>
            <small class="text-muted">PHP {{ envInfo.php_version || '—' }}</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Sub-Navigation Tabs -->
    <ul class="nav nav-tabs" style="margin-bottom:20px;">
      <li :class="{ active: activeTab === 'migrations' }">
        <a href="#" @click.prevent="activeTab = 'migrations'">
          <i class="glyphicon glyphicon-transfer text-primary"></i>
          Migraciones y Cambios Aplicados
          <span v-if="pendingCount > 0" class="badge" style="background:#d9534f;margin-left:4px;">{{ pendingCount }}</span>
        </a>
      </li>
      <li :class="{ active: activeTab === 'general' }">
        <a href="#" @click.prevent="activeTab = 'general'">
          <i class="glyphicon glyphicon-cog text-success"></i>
          Configuración General
        </a>
      </li>
      <li :class="{ active: activeTab === 'inventory' }">
        <a href="#" @click.prevent="activeTab = 'inventory'">
          <i class="glyphicon glyphicon-list-alt text-info"></i>
          Inventario de Base de Datos
        </a>
      </li>
    </ul>

    <!-- Loading -->
    <div v-if="loading" class="text-center" style="padding:40px 0;">
      <i class="glyphicon glyphicon-refresh" style="font-size:28px;animation:spin 1s linear infinite;color:#888;"></i>
      <p class="text-muted" style="margin-top:8px;">Cargando configuración del sistema…</p>
    </div>

    <template v-else>
      <!-- ══════════════════════════════════════════════════════════════════ -->
      <!-- TAB 1: DATABASE MIGRATIONS & APPLIED CHANGES LIST                  -->
      <!-- ══════════════════════════════════════════════════════════════════ -->
      <div v-show="activeTab === 'migrations'">
        <!-- Last execution result banner -->
        <div
          v-if="lastRunReport"
          :class="['alert', lastRunReport.success ? 'alert-success' : 'alert-danger']"
          style="margin-bottom:18px;"
        >
          <button type="button" class="close" @click="lastRunReport = null" aria-label="Cerrar">&times;</button>
          <strong>
            <i :class="lastRunReport.success ? 'glyphicon glyphicon-ok-sign' : 'glyphicon glyphicon-exclamation-sign'"></i>
            {{ lastRunReport.messages }}
          </strong>
          <ul v-if="lastRunReport.changes && lastRunReport.changes.length" style="margin:8px 0 0 18px;font-size:12.5px;">
            <li v-for="(ch, idx) in lastRunReport.changes" :key="idx">
              <span class="label" :class="ch.status === 'APPLIED' ? 'label-success' : 'label-danger'" style="margin-right:5px;">
                {{ ch.status === 'APPLIED' ? 'OK' : 'ERROR' }}
              </span>
              <strong>[{{ ch.object_type }}]</strong> {{ ch.summary }}
              <span v-if="ch.error" class="text-danger"> — {{ ch.error }}</span>
            </li>
          </ul>
        </div>

        <!-- Pending / Available Migrations Panel -->
        <div class="panel panel-default" style="margin-bottom:20px;">
          <div class="panel-heading panel-heading-flex">
            <div class="page-heading">
              <i class="glyphicon glyphicon-wrench text-primary" style="margin-right:8px;"></i>
              Integración y Ejecución de Migraciones SQL
            </div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              <button class="btn btn-default btn-sm" @click="loadConfig" :disabled="runningMigration">
                <i class="glyphicon glyphicon-refresh"></i> Actualizar
              </button>
              <button class="btn btn-success btn-sm" @click="openIntegrateModal" :disabled="runningMigration">
                <i class="glyphicon glyphicon-plus-sign"></i> Integrar Nueva Migración
              </button>
              <button
                class="btn btn-primary btn-sm"
                @click="runAllPending"
                :disabled="runningMigration || pendingCount === 0"
              >
                <i class="glyphicon glyphicon-play"></i>
                {{ runningMigration ? 'Ejecutando…' : `Ejecutar Pendientes (${pendingCount})` }}
              </button>
            </div>
          </div>

          <div class="panel-body">
            <div class="alert alert-info" style="font-size:12.5px;margin-bottom:14px;">
              <i class="glyphicon glyphicon-info-sign"></i>
              Desde este módulo puede ejecutar migraciones pendientes del directorio <code>database/migrations/</code> o
              <strong>integrar nuevas migraciones</strong> (nuevas tablas, alteraciones <code>ALTER TABLE</code>, vistas,
              procedimientos almacenados con <code>DELIMITER ;;</code> y triggers) directamente mediante botones.
            </div>

            <div class="table-responsive">
              <table class="table table-hover table-condensed">
                <thead>
                  <tr style="background:#f9f9f9;">
                    <th style="width:4%;">#</th>
                    <SortableTh label="Migración / Archivo" sort-key="title" :active-key="migSortKey" :sort-dir="migSortDir" @sort="toggleMigSort" />
                    <SortableTh label="Objetos BD" sort-key="object_types" :active-key="migSortKey" :sort-dir="migSortDir" @sort="toggleMigSort" />
                    <SortableTh label="Sentencias" sort-key="statement_count" :active-key="migSortKey" :sort-dir="migSortDir" align="center" @sort="toggleMigSort" />
                    <SortableTh label="Estado" sort-key="status" :active-key="migSortKey" :sort-dir="migSortDir" align="center" @sort="toggleMigSort" />
                    <SortableTh label="Última Ejecución" sort-key="applied_at" :active-key="migSortKey" :sort-dir="migSortDir" @sort="toggleMigSort" />
                    <th class="text-right" style="min-width:190px;">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="sortedMigrations.length === 0">
                    <td colspan="7" class="text-center text-muted" style="padding:20px;">
                      No se encontraron scripts de migración en <code>database/migrations/</code>.
                    </td>
                  </tr>
                  <tr v-for="(mig, idx) in sortedMigrations" :key="mig.migration_key">
                    <td>{{ idx + 1 }}</td>
                    <td>
                      <strong>{{ mig.title }}</strong><br />
                      <small class="text-muted"><code>{{ mig.filename }}</code></small>
                      <div v-if="mig.description" style="font-size:11.5px;color:#666;margin-top:2px;">
                        {{ mig.description }}
                      </div>
                      <div v-if="mig.error_message" class="text-danger" style="font-size:11.5px;margin-top:2px;">
                        <i class="glyphicon glyphicon-warning-sign"></i> {{ mig.error_message }}
                      </div>
                    </td>
                    <td>
                      <span
                        v-for="obj in mig.object_types"
                        :key="obj"
                        class="label label-info"
                        style="margin-right:4px;font-size:10px;"
                      >{{ obj }}</span>
                    </td>
                    <td class="text-center">{{ mig.statement_count }}</td>
                    <td class="text-center">
                      <span :class="'label label-' + migrationBadgeClass(mig.status)">
                        {{ migrationStatusLabel(mig.status) }}
                      </span>
                    </td>
                    <td>
                      <span v-if="mig.applied_at" style="font-size:12px;">
                        {{ mig.applied_at }}
                        <br /><small class="text-muted">{{ mig.execution_ms }} ms</small>
                      </span>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <td class="text-right">
                      <button
                        type="button"
                        class="btn btn-xs btn-default"
                        style="margin-right:4px;"
                        @click="inspectSql(mig)"
                      >
                        <i class="glyphicon glyphicon-eye-open"></i> Ver SQL
                      </button>
                      <button
                        v-if="mig.status !== 'APPLIED'"
                        type="button"
                        class="btn btn-xs btn-success"
                        :disabled="runningMigration"
                        @click="runSingleMigration(mig, true)"
                      >
                        <i class="glyphicon glyphicon-play"></i> Ejecutar
                      </button>
                      <button
                        v-else
                        type="button"
                        class="btn btn-xs btn-warning"
                        :disabled="runningMigration"
                        @click="runSingleMigration(mig, true)"
                        title="Re-ejecutar migración idempotente"
                      >
                        <i class="glyphicon glyphicon-repeat"></i> Re-ejecutar
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Applied Changes History Panel -->
        <div class="panel panel-default">
          <div class="panel-heading">
            <i class="glyphicon glyphicon-ok-circle text-success" style="margin-right:6px;"></i>
            <strong>Lista de Cambios Aplicados (Historial de Migraciones)</strong>
          </div>
          <div class="panel-body">
            <div class="table-responsive">
              <table class="table table-striped table-condensed" style="margin-bottom:0;">
                <thead>
                  <tr>
                    <SortableTh label="Fecha y Hora" sort-key="executed_at" :active-key="histSortKey" :sort-dir="histSortDir" @sort="toggleHistSort" />
                    <SortableTh label="Migración" sort-key="title" :active-key="histSortKey" :sort-dir="histSortDir" @sort="toggleHistSort" />
                    <SortableTh label="Categoría" sort-key="category" :active-key="histSortKey" :sort-dir="histSortDir" @sort="toggleHistSort" />
                    <SortableTh label="Estado" sort-key="status" :active-key="histSortKey" :sort-dir="histSortDir" @sort="toggleHistSort" />
                    <SortableTh label="Cambios Aplicados sobre Objetos BD" sort-key="changes_applied" :active-key="histSortKey" :sort-dir="histSortDir" @sort="toggleHistSort" />
                    <SortableTh label="Duración" sort-key="execution_ms" :active-key="histSortKey" :sort-dir="histSortDir" align="right" @sort="toggleHistSort" />
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="sortedHistory.length === 0">
                    <td colspan="6" class="text-center text-muted" style="padding:20px;">
                      Aún no se han ejecutado migraciones registradas en <code>schema_migrations</code>.
                    </td>
                  </tr>
                  <tr v-for="item in sortedHistory" :key="item.migration_id">
                    <td style="white-space:nowrap;font-size:12px;">
                      {{ item.executed_at }}<br />
                      <small class="text-muted">por <strong>{{ item.executed_by_username }}</strong></small>
                    </td>
                    <td>
                      <strong>{{ item.title }}</strong><br />
                      <small class="text-muted"><code>{{ item.migration_key }}</code></small>
                    </td>
                    <td><span class="label label-default">{{ item.category }}</span></td>
                    <td>
                      <span :class="item.status === 'APPLIED' ? 'label label-success' : 'label label-danger'">
                        {{ item.status === 'APPLIED' ? 'APLICADA' : 'ERROR' }}
                      </span>
                    </td>
                    <td>
                      <ul v-if="item.changes_applied && item.changes_applied.length" class="list-unstyled" style="margin:0;font-size:12px;line-height:1.5;">
                        <li v-for="(ch, ci) in item.changes_applied" :key="ci">
                          <i
                            :class="ch.status === 'APPLIED' ? 'glyphicon glyphicon-ok text-success' : 'glyphicon glyphicon-remove text-danger'"
                            style="margin-right:4px;"
                          ></i>
                          <span class="label label-info" style="font-size:9.5px;padding:1px 4px;margin-right:4px;">{{ ch.object_type }}</span>
                          <code>{{ ch.summary }}</code>
                          <span v-if="ch.error" class="text-danger"> — {{ ch.error }}</span>
                        </li>
                      </ul>
                      <span v-else class="text-muted">—</span>
                    </td>
                    <td class="text-right" style="white-space:nowrap;">
                      {{ item.execution_ms }} ms<br />
                      <small class="text-muted">{{ item.statements_executed }} sent.</small>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════════════════ -->
      <!-- TAB 2: GENERAL SYSTEM SETTINGS & AUDIT LOGS                        -->
      <!-- ══════════════════════════════════════════════════════════════════ -->
      <div v-show="activeTab === 'general'">
        <div v-if="!settingsTableExists" class="alert alert-warning">
          <i class="glyphicon glyphicon-exclamation-sign"></i>
          La tabla <code>system_settings</code> aún no ha sido creada en la base de datos.
          <button type="button" class="btn btn-xs btn-success" style="margin-left:10px;" :disabled="runningMigration" @click="bootstrapSettingsMigration">
            <i class="glyphicon glyphicon-play"></i> Ejecutar Migración 001 ahora
          </button>
        </div>

        <div class="panel panel-default" style="margin-bottom:20px;">
          <div class="panel-heading">
            <i class="glyphicon glyphicon-cog text-success" style="margin-right:6px;"></i>
            <strong>Parámetros Generales del Sistema</strong>
          </div>
          <div class="panel-body">
            <form class="form-horizontal" @submit.prevent="saveSystemSettings">
              <div class="form-group">
                <label class="col-sm-3 control-label">Nombre de la Aplicación</label>
                <div class="col-sm-4">
                  <input type="text" class="form-control" v-model="settingsForm.app_name" required />
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-3 control-label">Símbolo de Moneda</label>
                <div class="col-sm-2">
                  <input type="text" class="form-control" v-model="settingsForm.currency_symbol" maxlength="8" required />
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-3 control-label">Cuota Mensual Predeterminada</label>
                <div class="col-sm-3">
                  <input type="number" step="0.01" min="1" class="form-control" v-model="settingsForm.default_monthly_limit" required />
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-3 control-label">Expiración de Sesión (minutos)</label>
                <div class="col-sm-2">
                  <input type="number" min="5" max="1440" class="form-control" v-model="settingsForm.session_lifetime_minutes" required />
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-3 control-label">Registro Público de Usuarios</label>
                <div class="col-sm-4">
                  <select class="form-control" v-model="settingsForm.allow_user_registration">
                    <option value="1">Habilitado (usuarios pueden registrarse)</option>
                    <option value="0">Deshabilitado (solo cuentas existentes)</option>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-3 control-label">Modo Mantenimiento</label>
                <div class="col-sm-4">
                  <select class="form-control" v-model="settingsForm.maintenance_mode">
                    <option value="0">Inactivo (operación normal)</option>
                    <option value="1">Activo (solo acceso administrador)</option>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <div class="col-sm-offset-3 col-sm-9">
                  <button type="submit" class="btn btn-success" :disabled="savingSettings || !settingsTableExists">
                    <i class="glyphicon glyphicon-floppy-disk"></i>
                    {{ savingSettings ? 'Guardando…' : 'Guardar Configuración General' }}
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- System Overview View & Audit Log -->
        <div v-if="overview" class="panel panel-default" style="margin-bottom:20px;">
          <div class="panel-heading">
            <i class="glyphicon glyphicon-eye-open text-info" style="margin-right:6px;"></i>
            <strong>Resumen Global desde Vista SQL (<code>v_system_overview</code>)</strong>
          </div>
          <div class="panel-body">
            <div class="row text-center">
              <div class="col-xs-6 col-sm-3"><strong>{{ overview.active_users }}</strong><br /><small class="text-muted">Usuarios activos</small></div>
              <div class="col-xs-6 col-sm-3"><strong>{{ overview.active_accounts }}</strong><br /><small class="text-muted">Cuentas activas</small></div>
              <div class="col-xs-6 col-sm-3"><strong>{{ overview.total_incomes_count }} / {{ overview.total_expenses_count }}</strong><br /><small class="text-muted">Ingresos / Gastos</small></div>
              <div class="col-xs-6 col-sm-3"><strong>{{ overview.total_loans_count }} / {{ overview.total_borrowers_count }}</strong><br /><small class="text-muted">Préstamos / Prestatarios</small></div>
            </div>
          </div>
        </div>

        <div class="panel panel-default">
          <div class="panel-heading">
            <i class="glyphicon glyphicon-list text-warning" style="margin-right:6px;"></i>
            <strong>Auditoría de Cambios del Sistema (<code>system_audit_logs</code>)</strong>
          </div>
          <div class="panel-body">
            <div class="table-responsive">
              <table class="table table-condensed table-striped" style="margin-bottom:0;">
                <thead>
                  <tr>
                    <SortableTh label="Fecha" sort-key="created_at" :active-key="auditSortKey" :sort-dir="auditSortDir" @sort="toggleAuditSort" />
                    <SortableTh label="Evento" sort-key="event_type" :active-key="auditSortKey" :sort-dir="auditSortDir" @sort="toggleAuditSort" />
                    <SortableTh label="Clave" sort-key="target_key" :active-key="auditSortKey" :sort-dir="auditSortDir" @sort="toggleAuditSort" />
                    <SortableTh label="Valor Anterior" sort-key="old_value" :active-key="auditSortKey" :sort-dir="auditSortDir" @sort="toggleAuditSort" />
                    <SortableTh label="Valor Nuevo" sort-key="new_value" :active-key="auditSortKey" :sort-dir="auditSortDir" @sort="toggleAuditSort" />
                    <SortableTh label="Usuario" sort-key="performed_by_username" :active-key="auditSortKey" :sort-dir="auditSortDir" @sort="toggleAuditSort" />
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="sortedAuditLogs.length === 0">
                    <td colspan="6" class="text-center text-muted" style="padding:16px;">Sin eventos de auditoría registrados aún.</td>
                  </tr>
                  <tr v-for="log in sortedAuditLogs" :key="log.audit_id">
                    <td>{{ log.created_at }}</td>
                    <td><span class="label label-info">{{ log.event_type }}</span></td>
                    <td><code>{{ log.target_key }}</code></td>
                    <td>{{ log.old_value ?? '—' }}</td>
                    <td><strong>{{ log.new_value ?? '—' }}</strong></td>
                    <td>{{ log.performed_by_username || 'sistema' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════════════════════ -->
      <!-- TAB 3: DATABASE OBJECT INVENTORY                                   -->
      <!-- ══════════════════════════════════════════════════════════════════ -->
      <div v-show="activeTab === 'inventory'">
        <div class="row">
          <div class="col-xs-12 col-md-6">
            <div class="panel panel-default">
              <div class="panel-heading"><strong>Tablas ({{ inventory.tables.length }})</strong></div>
              <div class="table-responsive">
                <table class="table table-condensed table-striped" style="margin-bottom:0;font-size:12.5px;">
                  <thead>
                    <tr>
                      <SortableTh label="Tabla" sort-key="name" :active-key="tblSortKey" :sort-dir="tblSortDir" @sort="toggleTblSort" />
                      <SortableTh label="Motor" sort-key="engine" :active-key="tblSortKey" :sort-dir="tblSortDir" @sort="toggleTblSort" />
                      <SortableTh label="Filas aprox." sort-key="rows" :active-key="tblSortKey" :sort-dir="tblSortDir" align="right" @sort="toggleTblSort" />
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="t in sortedInvTables" :key="t.name">
                      <td><code>{{ t.name }}</code></td>
                      <td>{{ t.engine }}</td>
                      <td class="text-right">{{ t.rows }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="col-xs-12 col-md-6">
            <div class="panel panel-default" style="margin-bottom:15px;">
              <div class="panel-heading"><strong>Vistas SQL ({{ inventory.views.length }})</strong></div>
              <div class="table-responsive">
                <table class="table table-condensed table-striped" style="margin-bottom:0;font-size:12.5px;">
                  <thead>
                    <tr>
                      <SortableTh label="Vista" sort-key="name" :active-key="viewSortKey" :sort-dir="viewSortDir" @sort="toggleViewSort" />
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="v in sortedInvViews" :key="v.name"><td><code>{{ v.name }}</code></td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="panel panel-default" style="margin-bottom:15px;">
              <div class="panel-heading"><strong>Procedimientos Almacenados ({{ inventory.procedures.length }})</strong></div>
              <div class="table-responsive">
                <table class="table table-condensed table-striped" style="margin-bottom:0;font-size:12.5px;">
                  <thead>
                    <tr>
                      <SortableTh label="Nombre" sort-key="name" :active-key="procSortKey" :sort-dir="procSortDir" @sort="toggleProcSort" />
                      <SortableTh label="Tipo" sort-key="type" :active-key="procSortKey" :sort-dir="procSortDir" @sort="toggleProcSort" />
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="p in sortedInvProcedures" :key="p.name">
                      <td><code>{{ p.name }}</code></td>
                      <td><span class="label label-warning">{{ p.type }}</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="panel panel-default">
              <div class="panel-heading"><strong>Triggers ({{ inventory.triggers.length }})</strong></div>
              <div class="table-responsive">
                <table class="table table-condensed table-striped" style="margin-bottom:0;font-size:12.5px;">
                  <thead>
                    <tr>
                      <SortableTh label="Trigger" sort-key="name" :active-key="trgSortKey" :sort-dir="trgSortDir" @sort="toggleTrgSort" />
                      <SortableTh label="Tabla" sort-key="table" :active-key="trgSortKey" :sort-dir="trgSortDir" @sort="toggleTrgSort" />
                      <SortableTh label="Momento" sort-key="timing" :active-key="trgSortKey" :sort-dir="trgSortDir" @sort="toggleTrgSort" />
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="tr in sortedInvTriggers" :key="tr.name">
                      <td><code>{{ tr.name }}</code></td>
                      <td>{{ tr.table }}</td>
                      <td><span class="label label-default">{{ tr.timing }}</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Integrate New Migration Modal -->
    <AppModal v-model="showIntegrateModal" title="Integrar Nueva Migración SQL" icon="glyphicon-plus-sign"
              header-color="#27ae60" size="lg">
      <template #body>
        <div v-if="integrateError" class="alert alert-danger">{{ integrateError }}</div>

        <div class="form-group">
          <label>Plantillas rápidas por tipo de objeto:</label>
          <div style="display:flex;flex-wrap:wrap;gap:6px;">
            <button type="button" class="btn btn-xs btn-default" @click="applyTemplate('table')">
              <i class="glyphicon glyphicon-th-large"></i> Nueva Tabla
            </button>
            <button type="button" class="btn btn-xs btn-default" @click="applyTemplate('alter')">
              <i class="glyphicon glyphicon-edit"></i> Modificar Tabla (ALTER)
            </button>
            <button type="button" class="btn btn-xs btn-default" @click="applyTemplate('view')">
              <i class="glyphicon glyphicon-eye-open"></i> Crear / Reemplazar Vista
            </button>
            <button type="button" class="btn btn-xs btn-default" @click="applyTemplate('procedure')">
              <i class="glyphicon glyphicon-flash"></i> Procedimiento Almacenado
            </button>
            <button type="button" class="btn btn-xs btn-default" @click="applyTemplate('trigger')">
              <i class="glyphicon glyphicon-bell"></i> Trigger
            </button>
          </div>
        </div>

        <div class="row">
          <div class="col-sm-6">
            <div class="form-group">
              <label>Título de la migración <span class="text-danger">*</span></label>
              <input type="text" class="form-control" v-model="integrateForm.title" placeholder="Ej: Agregar índice o tabla de etiquetas" />
            </div>
          </div>
          <div class="col-sm-6">
            <div class="form-group">
              <label>Cargar archivo <code>.sql</code> (opcional)</label>
              <input ref="sqlFileInputRef" type="file" accept=".sql" class="form-control" @change="onSqlFilePicked" />
            </div>
          </div>
        </div>

        <div class="form-group">
          <label>Descripción</label>
          <input type="text" class="form-control" v-model="integrateForm.description" placeholder="Descripción breve de los cambios sobre tablas, vistas, SP o triggers" />
        </div>

        <div class="form-group" style="margin-bottom:0;">
          <label>Script SQL <span class="text-danger">*</span></label>
          <textarea
            class="form-control"
            rows="10"
            style="font-family:monospace;font-size:12px;"
            v-model="integrateForm.sql"
            placeholder="CREATE TABLE IF NOT EXISTS ... / ALTER TABLE ... / CREATE OR REPLACE VIEW ... / DELIMITER ;; CREATE PROCEDURE ..."
          ></textarea>
        </div>
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showIntegrateModal = false">Cancelar</button>
        <button class="btn btn-info" :disabled="integrating" @click="submitIntegrate(false)">
          <i class="glyphicon glyphicon-floppy-disk"></i>
          Guardar Archivo (.sql)
        </button>
        <button class="btn btn-success" :disabled="integrating" @click="submitIntegrate(true)">
          <i class="glyphicon glyphicon-play"></i>
          {{ integrating ? 'Ejecutando…' : 'Integrar y Ejecutar Ahora' }}
        </button>
      </template>
    </AppModal>

    <!-- View SQL Modal -->
    <AppModal v-model="showSqlModal" :title="'SQL: ' + (inspectedMig?.title || '')" icon="glyphicon-file" size="lg">
      <template #body>
        <p class="text-muted" style="margin-bottom:8px;">
          Archivo: <code>{{ inspectedMig?.filename }}</code>
        </p>
        <pre style="max-height:420px;overflow:auto;font-size:12px;background:#f8f9fa;border:1px solid #ddd;padding:12px;">{{ inspectedMig?.sql }}</pre>
      </template>
      <template #footer>
        <button class="btn btn-default" @click="showSqlModal = false">Cerrar</button>
      </template>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, inject, onMounted } from 'vue'
import { useAuthStore } from '@/stores/useAuthStore'
import AppModal from '@/components/AppModal.vue'
import SortableTh from '@/components/SortableTh.vue'
import { useTableSort } from '@/utils/sort'
import * as api from '@/api'

const toast     = inject('toast')
const authStore = useAuthStore()

const activeTab          = ref('migrations')
const loading            = ref(true)
const runningMigration   = ref(false)
const savingSettings     = ref(false)
const integrating        = ref(false)
const settingsTableExists = ref(false)
const lastRunReport      = ref(null)

const envInfo    = ref({ php_version: '', db_version: '', db_name: '', tables_count: 0, views_count: 0, procedures_count: 0, triggers_count: 0 })
const migrations = ref([])
const history    = ref([])
const overview   = ref(null)
const auditLogs  = ref([])
const inventory  = ref({ tables: [], views: [], procedures: [], triggers: [] })

const settingsForm = reactive({
  app_name: 'myFin',
  currency_symbol: 'S/',
  default_monthly_limit: '4000.00',
  session_lifetime_minutes: '30',
  allow_user_registration: '1',
  maintenance_mode: '0',
})

const showIntegrateModal = ref(false)
const integrateError     = ref('')
const sqlFileInputRef    = ref(null)
const integrateForm      = reactive({
  title: '',
  description: '',
  sql: '',
})

const showSqlModal = ref(false)
const inspectedMig = ref(null)

const appliedCount = computed(() => migrations.value.filter(m => m.status === 'APPLIED').length)
const pendingCount = computed(() => migrations.value.filter(m => m.status !== 'APPLIED').length)

const { sortKey: migSortKey, sortDir: migSortDir, toggleSort: toggleMigSort, sortRows: sortMigRows } = useTableSort('', 'asc', {
  object_types: r => (r.object_types || []).join(', '),
  status:       r => migrationStatusLabel(r.status),
})
const sortedMigrations = computed(() => sortMigRows(migrations.value))

const { sortKey: histSortKey, sortDir: histSortDir, toggleSort: toggleHistSort, sortRows: sortHistRows } = useTableSort('', 'desc', {
  changes_applied: r => (r.changes_applied || []).join(', '),
})
const sortedHistory = computed(() => sortHistRows(history.value))

const { sortKey: auditSortKey, sortDir: auditSortDir, toggleSort: toggleAuditSort, sortRows: sortAuditRows } = useTableSort('', 'desc')
const sortedAuditLogs = computed(() => sortAuditRows(auditLogs.value))

const { sortKey: tblSortKey, sortDir: tblSortDir, toggleSort: toggleTblSort, sortRows: sortTblRows } = useTableSort('name', 'asc')
const sortedInvTables = computed(() => sortTblRows(inventory.value.tables || []))

const { sortKey: viewSortKey, sortDir: viewSortDir, toggleSort: toggleViewSort, sortRows: sortViewRows } = useTableSort('name', 'asc')
const sortedInvViews = computed(() => sortViewRows(inventory.value.views || []))

const { sortKey: procSortKey, sortDir: procSortDir, toggleSort: toggleProcSort, sortRows: sortProcRows } = useTableSort('name', 'asc')
const sortedInvProcedures = computed(() => sortProcRows(inventory.value.procedures || []))

const { sortKey: trgSortKey, sortDir: trgSortDir, toggleSort: toggleTrgSort, sortRows: sortTrgRows } = useTableSort('name', 'asc')
const sortedInvTriggers = computed(() => sortTrgRows(inventory.value.triggers || []))

function migrationBadgeClass(st) {
  if (st === 'APPLIED')  return 'success'
  if (st === 'MODIFIED') return 'warning'
  if (st === 'FAILED')   return 'danger'
  return 'default'
}

function migrationStatusLabel(st) {
  if (st === 'APPLIED')  return 'APLICADA'
  if (st === 'MODIFIED') return 'MODIFICADA'
  if (st === 'FAILED')   return 'ERROR'
  return 'PENDIENTE'
}

async function loadConfig() {
  loading.value = true
  try {
    const { data } = await api.adminGetSystemConfig()
    if (data?.success) {
      settingsTableExists.value = Boolean(data.settings_table_exists)
      envInfo.value             = data.environment || envInfo.value
      migrations.value          = data.migrations || []
      history.value             = data.history || []
      overview.value            = data.overview || null
      auditLogs.value           = data.audit_logs || []
      inventory.value           = data.inventory || { tables: [], views: [], procedures: [], triggers: [] }
      if (data.settings) {
        Object.assign(settingsForm, data.settings)
      }
    } else {
      toast.danger(data?.messages || 'No se pudo cargar la configuración.')
    }
  } catch (e) {
    toast.danger(e.response?.data?.messages || 'Error cargando módulo de administración.')
  } finally {
    loading.value = false
  }
}

async function runAllPending() {
  runningMigration.value = true
  try {
    const fd = new FormData()
    fd.append('action', 'run_all')
    const { data } = await api.adminRunMigrations(fd)
    lastRunReport.value = data
    if (data.success) toast.success(data.messages)
    else toast.danger(data.messages)
    await loadConfig()
  } catch (e) {
    toast.danger(e.response?.data?.messages || 'Error ejecutando migraciones.')
  } finally {
    runningMigration.value = false
  }
}

async function runSingleMigration(mig, force = false) {
  runningMigration.value = true
  try {
    const fd = new FormData()
    fd.append('action', 'run_one')
    fd.append('migration_key', mig.migration_key)
    if (force) fd.append('force', '1')
    const { data } = await api.adminRunMigrations(fd)
    lastRunReport.value = data
    if (data.success) toast.success(data.messages)
    else toast.danger(data.messages)
    await loadConfig()
  } catch (e) {
    toast.danger(e.response?.data?.messages || 'Error ejecutando migración.')
  } finally {
    runningMigration.value = false
  }
}

async function bootstrapSettingsMigration() {
  const mig = migrations.value.find(m => m.migration_key.startsWith('001_'))
  if (mig) {
    await runSingleMigration(mig, true)
  } else {
    await runAllPending()
  }
}

async function saveSystemSettings() {
  savingSettings.value = true
  try {
    const fd = new FormData()
    Object.entries(settingsForm).forEach(([k, v]) => fd.append(k, v))
    const { data } = await api.adminUpdateSystemSettings(fd)
    if (data?.success) {
      toast.success(data.messages)
      if (data.app_name) authStore.setAppName(data.app_name)
      await loadConfig()
    } else {
      toast.danger(data?.messages || 'No se pudieron guardar los parámetros.')
    }
  } catch (e) {
    toast.danger(e.response?.data?.messages || 'Error guardando configuración.')
  } finally {
    savingSettings.value = false
  }
}

function openIntegrateModal() {
  integrateError.value    = ''
  integrateForm.title       = ''
  integrateForm.description = ''
  integrateForm.sql         = ''
  if (sqlFileInputRef.value) sqlFileInputRef.value.value = ''
  showIntegrateModal.value = true
}

function onSqlFilePicked(e) {
  const file = e.target.files?.[0]
  if (!file) return
  if (!integrateForm.title) {
    integrateForm.title = file.name.replace(/\.sql$/i, '')
  }
  const reader = new FileReader()
  reader.onload = () => {
    integrateForm.sql = String(reader.result || '')
  }
  reader.readAsText(file)
}

function applyTemplate(type) {
  const templates = {
    table: {
      title: 'Crear nueva tabla auxiliar',
      description: 'Crea una nueva tabla en el esquema actual',
      sql: `CREATE TABLE IF NOT EXISTS \`mi_nueva_tabla\` (\n  \`id\` INT AUTO_INCREMENT PRIMARY KEY,\n  \`nombre\` VARCHAR(150) NOT NULL,\n  \`created_at\` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;`
    },
    alter: {
      title: 'Modificar tabla existente',
      description: 'Agrega columna o índice en una tabla existente',
      sql: `ALTER TABLE \`loans\`\n  ADD COLUMN IF NOT EXISTS \`reference_code\` VARCHAR(50) NULL DEFAULT NULL;`
    },
    view: {
      title: 'Crear o reemplazar vista SQL',
      description: 'Define o actualiza una vista SQL de consulta',
      sql: `CREATE OR REPLACE VIEW \`v_custom_summary\` AS\nSELECT user_id, username, role, is_active FROM \`users\`;`
    },
    procedure: {
      title: 'Crear procedimiento almacenado',
      description: 'Define o reemplaza un procedimiento almacenado con DELIMITER',
      sql: `DROP PROCEDURE IF EXISTS \`sp_custom_check\`;\nDELIMITER ;;\nCREATE PROCEDURE \`sp_custom_check\`()\nBEGIN\n  SELECT NOW() AS checked_at;\nEND ;;\nDELIMITER ;`
    },
    trigger: {
      title: 'Crear trigger de base de datos',
      description: 'Define un trigger AFTER UPDATE con DELIMITER',
      sql: `DROP TRIGGER IF EXISTS \`trg_custom_audit\`;\nDELIMITER ;;\nCREATE TRIGGER \`trg_custom_audit\`\nAFTER UPDATE ON \`system_settings\`\nFOR EACH ROW\nBEGIN\n  -- lógica del trigger\nEND ;;\nDELIMITER ;`
    }
  }
  const tpl = templates[type]
  if (!tpl) return
  integrateForm.title       = tpl.title
  integrateForm.description = tpl.description
  integrateForm.sql         = tpl.sql
}

async function submitIntegrate(executeNow) {
  integrateError.value = ''
  if (!integrateForm.title.trim() || !integrateForm.sql.trim()) {
    integrateError.value = 'Ingrese el título y el script SQL.'
    return
  }
  integrating.value = true
  try {
    const fd = new FormData()
    fd.append('title', integrateForm.title.trim())
    fd.append('description', integrateForm.description.trim())
    fd.append('sql', integrateForm.sql)
    fd.append('execute_now', executeNow ? '1' : '0')
    const { data } = await api.adminIntegrateMigration(fd)
    if (data?.success) {
      showIntegrateModal.value = false
      lastRunReport.value = data
      toast.success(data.messages)
      await loadConfig()
    } else {
      integrateError.value = data?.messages || 'No se pudo integrar/ejecutar la migración.'
      if (data?.changes) lastRunReport.value = data
      await loadConfig()
    }
  } catch (e) {
    integrateError.value = e.response?.data?.messages || 'Error de comunicación.'
  } finally {
    integrating.value = false
  }
}

function inspectSql(mig) {
  inspectedMig.value = mig
  showSqlModal.value = true
}

onMounted(loadConfig)
</script>
