ALTER TABLE  `tbl_users` ADD  `col_ext` VARCHAR( 20 ) NOT NULL AFTER  `col_phone` ;
ALTER TABLE  `tbl_users` CHANGE  `col_estudios`  `col_estudios` VARCHAR( 150 ) NOT NULL ;
ALTER TABLE  `tbl_carreras` CHANGE  `col_duracion`  `col_duracion` FLOAT( 2, 1 ) NOT NULL DEFAULT  '1.0';
ALTER TABLE  `tbl_carreras` ADD  `col_actualizacion` DATE NOT NULL AFTER  `col_modalidad` ,
ADD  `col_campus` TINYINT( 2 ) NOT NULL DEFAULT  '0' AFTER  `col_actualizacion` ;


/*  22/10/2018 */
ALTER TABLE  `tbl_pagos` DROP  `col_folio` ,
DROP  `col_nota` ,
DROP  `col_fecha_de_pago` ;
ALTER TABLE  `tbl_pagos` CHANGE  `col_alumnoid`  `col_alumno` VARCHAR( 200 ) NULL DEFAULT NULL ;
ALTER TABLE  `tbl_users` ADD UNIQUE (`col_email`);
ALTER TABLE  `tbl_alumnos` ADD UNIQUE (`col_correo`);
ALTER TABLE  `tbl_alumnos` ADD  `col_correo_personal` VARCHAR( 150 ) NOT NULL AFTER  `col_correo` ;
ALTER TABLE  `tbl_alumnos` ADD  `col_control` INT( 11 ) NOT NULL AFTER  `col_id` ;
ALTER TABLE  `tbl_alumnos` ADD  `col_proce_prepa` VARCHAR( 255 ) NOT NULL AFTER  `col_carrera` ,
ADD  `col_proce_prepa_promedio` FLOAT( 15, 2 ) NOT NULL AFTER  `col_proce_prepa` ,
ADD  `col_proce_licenciatura` VARCHAR( 255 ) NOT NULL AFTER  `col_proce_prepa_promedio` ,
ADD  `col_proce_licenciatura_promedio` FLOAT( 15, 2 ) NOT NULL AFTER  `col_proce_licenciatura` ,
ADD  `col_proce_maestria` VARCHAR( 255 ) NOT NULL AFTER  `col_proce_licenciatura_promedio` ,
ADD  `col_tipo_seguro` VARCHAR( 255 ) NOT NULL AFTER  `col_proce_maestria` ,
ADD  `col_trabajo` VARCHAR( 255 ) NOT NULL AFTER  `col_tipo_seguro` ,
ADD  `col_cargo_trabajo` VARCHAR( 255 ) NOT NULL AFTER  `col_trabajo` ;
CREATE TABLE IF NOT EXISTS `tbl_alumnos_taxonomia` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_alumnoid` int(11) NOT NULL,
  `col_periodoid` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
INSERT INTO `zadmin_univ`.`tbl_periodos` (`col_id`, `col_nombre`, `col_fecha_inicio`, `col_fecha_fin`, `col_created_at`, `col_created_by`, `col_updated_at`, `col_updated_by`) VALUES (NULL, 'Agosto - Diciembre 2018', '2018-08-01', '2018-12-31', '2018-10-22 00:00:00', '1', '2018-10-22 00:00:00', '1');
ALTER TABLE  `tbl_alumnos` CHANGE  `col_generacion`  `col_generacion_start` INT( 4 ) NOT NULL ;
ALTER TABLE  `tbl_alumnos` ADD  `col_generacion_end` INT( 4 ) NOT NULL AFTER  `col_generacion_start` ;


/*  28/10/2018 */
ALTER TABLE  `tbl_alumnos` ADD  `col_periodoid` INT( 11 ) NOT NULL ;
ALTER TABLE  `tbl_periodos` CHANGE  `col_fecha_inicio`  `col_grado` INT( 3 ) NULL DEFAULT  '1',
CHANGE  `col_fecha_fin`  `col_grupo` VARCHAR( 10 ) NULL DEFAULT  'A';
ALTER TABLE  `tbl_alumnos` CHANGE  `col_proce_licenciatura`  `col_proce_universidad_lic` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
CHANGE  `col_proce_licenciatura_promedio`  `col_proce_licenciatura` VARCHAR( 255 ) NOT NULL ,
CHANGE  `col_proce_maestria`  `col_proce_universidad_master` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;
ALTER TABLE  `tbl_alumnos` ADD  `col_proce_maestria` VARCHAR( 225 ) NOT NULL AFTER  `col_proce_universidad_master` ;
ALTER TABLE  `tbl_maestros` ADD  `col_costo_clase` FLOAT( 15, 2 ) NOT NULL ,
ADD  `col_costo_clase_academica` FLOAT( 15, 2 ) NOT NULL ;
ALTER TABLE  `tbl_users` CHANGE  `col_fecha_ingreso`  `col_fecha_ingreso_semestral` DATE NOT NULL ,
CHANGE  `col_fecha_termino`  `col_fecha_termino_semestral` DATE NOT NULL ;
ALTER TABLE  `tbl_departamentos` ADD  `col_permisos` INT( 2 ) NOT NULL DEFAULT  '1' AFTER  `col_abreviacion` ;
ALTER TABLE  `tbl_documentos` ADD  `col_descargable` ENUM(  '0',  '1' ) NOT NULL DEFAULT  '0' AFTER  `col_obligatorio` ,
ADD  `col_alumnos` ENUM(  '0',  '1' ) NOT NULL DEFAULT  '0' AFTER  `col_descargable` ,
ADD  `col_filepath` VARCHAR( 255 ) NOT NULL AFTER  `col_alumnos` ,
ADD  `col_filename` VARCHAR( 255 ) NOT NULL AFTER  `col_filepath` ;
ALTER TABLE  `tbl_documentos` ADD  `col_filetype` VARCHAR( 50 ) NOT NULL AFTER  `col_filename` ;


/* 01/11/2018 */
ALTER TABLE `tbl_users` ADD `col_fecha_ingreso` DATE NOT NULL AFTER `col_patronal`, ADD `col_fecha_termino` DATE NOT NULL AFTER `col_fecha_ingreso`;


/* 02/11/2018 */

ALTER TABLE  `tbl_alumnos` ADD  `col_egresado` TINYINT( 2 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `tbl_alumnos` CHANGE  `col_seguro`  `col_seguro` VARCHAR( 255 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `tbl_alumnos_taxonomia` ADD  `col_status` TINYINT( 2 ) NOT NULL DEFAULT  '0';

/* 05/11/2018 */
ALTER TABLE  `tbl_calificaciones` CHANGE  `col_grupoid`  `col_periodoid` INT( 11 ) NULL DEFAULT NULL ;
ALTER TABLE  `tbl_calificaciones` DROP  `col_actividadid` ;
ALTER TABLE  `tbl_calificaciones` DROP  `col_calificacion` ;
ALTER TABLE  `tbl_calificaciones` ADD  `col_p1` FLOAT( 3, 2 ) NOT NULL AFTER  `col_materiaid` ,
ADD  `col_ef` FLOAT( 3, 2 ) NOT NULL AFTER  `col_p1` ,
ADD  `col_cf` FLOAT( 3, 2 ) NOT NULL AFTER  `col_ef` ,
ADD  `col_ext` FLOAT( 3, 2 ) NOT NULL AFTER  `col_cf` ,
ADD  `col_ts` FLOAT( 3, 2 ) NOT NULL AFTER  `col_ext` ;

/* 08/11/2018 */
ALTER TABLE  `tbl_alumnos` ADD  `col_credencial` TINYINT( 2 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `tbl_alumnos` ADD  `col_referencia` VARCHAR( 150 ) NOT NULL DEFAULT  '';
CREATE TABLE IF NOT EXISTS `tbl_biblioteca` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_alumnoid` int(11) NOT NULL,
  `col_titulo_libro` varchar(100) NOT NULL,
  `col_clasificacion` varchar(20) NOT NULL,
  `col_adquisicion` int(10) NOT NULL,
  `col_fecha_prestamo` date NOT NULL,
  `col_fecha_devolucion` date NOT NULL,
  `col_identificacion` varchar(100) NOT NULL,
  `col_renovacion` enum('si','no') NOT NULL DEFAULT 'no',
  `col_fecha_renovacion` date NOT NULL,
  `col_fecha_entrega` date NOT NULL,
  `col_multa` varchar(50) NOT NULL,
  `col_created_at` datetime NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
ALTER TABLE  `tbl_biblioteca` ADD  `col_tipo_multa` TINYINT( 2 ) NOT NULL DEFAULT  '0' AFTER  `col_fecha_entrega` ;


/* 17/11/2018 */
ALTER TABLE  `tbl_biblioteca` ADD  `col_hora_devolucion` VARCHAR( 10 ) NOT NULL DEFAULT  '00:00' AFTER  `col_fecha_devolucion` ;
ALTER TABLE  `tbl_biblioteca` ADD  `col_hora_prestamo` VARCHAR( 10 ) NOT NULL DEFAULT  '00:00' AFTER  `col_fecha_prestamo` ;
ALTER TABLE  `tbl_biblioteca` ADD  `col_hora_entrega` VARCHAR( 10 ) NOT NULL DEFAULT  '00:00' AFTER  `col_fecha_entrega` ;
ALTER TABLE  `tbl_biblioteca` ADD  `col_hora_renovacion` VARCHAR( 10 ) NOT NULL DEFAULT  '00:00' AFTER  `col_fecha_renovacion` ;

/* 19/11/2018 */
ALTER TABLE  `tbl_alumnos` CHANGE  `col_documentos`  `col_documentos` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ;


/* 20/11/2018 */
CREATE TABLE IF NOT EXISTS `tbl_maestros_taxonomia` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_maestroid` int(11) NOT NULL,
  `col_materiaid` int(11) NOT NULL,
  `col_periodoid` int(11) NOT NULL,
  `col_created_at` datetime NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE  `tbl_maestros` ADD  `col_costo_clase_postgrado` FLOAT( 15, 2 ) NOT NULL;
ALTER TABLE  `tbl_users` ADD  `col_curp` VARCHAR( 100 ) NOT NULL ;

ALTER TABLE  `tbl_maestros` ADD  `fileCV` VARCHAR( 255 ) NOT NULL;
ALTER TABLE  `tbl_maestros` ADD  `fileActaNacimiento` VARCHAR( 255 ) NOT NULL;
ALTER TABLE  `tbl_maestros` ADD  `fileINE` VARCHAR( 255 ) NOT NULL;
ALTER TABLE  `tbl_maestros` ADD  `fileTituloLicenciatura` VARCHAR( 255 ) NOT NULL;
ALTER TABLE  `tbl_maestros` ADD  `fileCedulaLicenciatura` VARCHAR( 255 ) NOT NULL;
ALTER TABLE  `tbl_maestros` ADD  `fileGradoMaestria` VARCHAR( 255 ) NOT NULL;
ALTER TABLE  `tbl_maestros` ADD  `fileCedulaMaestria` VARCHAR( 255 ) NOT NULL;
ALTER TABLE  `tbl_maestros` ADD  `fileGradoDoctorado` VARCHAR( 255 ) NOT NULL;
ALTER TABLE  `tbl_maestros` ADD  `fileCedulaDoctorado` VARCHAR( 255 ) NOT NULL;
ALTER TABLE  `tbl_maestros` ADD  `fileContratoColaboracion` VARCHAR( 255 ) NOT NULL;

ALTER TABLE `tbl_maestros`
  DROP `col_cedula`,
  DROP `col_fecha_nacimiento`,
  DROP `col_dependencia`,
  DROP `col_seguro`;

ALTER TABLE  `tbl_maestros` CHANGE  `col_costo_clase_academica`  `col_costo_clase_academia` FLOAT( 15, 2 ) NOT NULL ;
ALTER TABLE  `tbl_maestros` ADD  `col_contratado` TINYINT( 2 ) NOT NULL DEFAULT  '1';

CREATE TABLE IF NOT EXISTS `tbl_alumnos_historial` (
  `col_id` int(11) NOT NULL,
  `col_alumnoid` int(11) NOT NULL,
  `col_accion` varchar(100) NOT NULL,
  `col_fecha` date NOT NULL,
  `col_fecha_expiracion` date NOT NULL,
  `col_parametros` varchar(255) NOT NULL,
  `col_observaciones` varchar(255) NOT NULL,
  `col_created_at` datetime NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE  `tbl_calificaciones` ADD  `col_p2` FLOAT( 3, 2 ) NOT NULL AFTER  `col_p1` ;
ALTER TABLE  `tbl_config` ADD  `col_postal` VARCHAR( 100 ) NOT NULL DEFAULT  '' AFTER  `col_config` ;
INSERT INTO `tbl_config` (`col_id`, `col_config`, `col_postal`, `col_updated_at`, `col_updated_by`) VALUES (NULL, NULL, '', NULL, NULL);
ALTER TABLE  `tbl_materias` ADD  `col_semestre` INT( 11 ) NOT NULL DEFAULT  '99' AFTER  `col_carrera` ;
ALTER TABLE  `tbl_carreras` ADD  `col_tipo` TINYINT( 2 ) NOT NULL DEFAULT  '0' AFTER  `col_campus` ;

CREATE TABLE IF NOT EXISTS `tbl_historial_impresiones` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_folio` int(11) NOT NULL DEFAULT '1',
  `col_documento` varchar(100) NOT NULL,
  `col_created_at` datetime NOT NULL,
  `col_created_by` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE  `tbl_periodos` ADD  `col_carreraid` INT( 11 ) NOT NULL AFTER  `col_grupo` ,
ADD  `col_transversales` VARCHAR( 255 ) NOT NULL AFTER  `col_carreraid` ,
ADD  `col_academia` VARCHAR( 255 ) NOT NULL AFTER  `col_transversales` ,
ADD  `col_club_lectura` VARCHAR( 255 ) NOT NULL AFTER  `col_academia` ;


/* 26/11/2018 */
ALTER TABLE `tbl_pagos` DROP `col_concepto`, DROP `col_alumno`, DROP `col_cantidad`;
ALTER TABLE  `tbl_pagos` ADD  `col_alumnoid` INT( 11 ) NOT NULL AFTER  `col_id` ,
ADD  `col_referencia` VARCHAR( 100 ) NOT NULL AFTER  `col_alumnoid` ,
ADD  `col_cargos_pagados` FLOAT( 15, 2 ) NOT NULL AFTER  `col_referencia` ,
ADD  `col_recargos_pagados` FLOAT( 15, 2 ) NOT NULL AFTER  `col_cargos_pagados` ,
ADD  `col_total_pagado` FLOAT( 15, 2 ) NOT NULL AFTER  `col_recargos_pagados` ,
ADD  `col_cargos_vencidos` FLOAT( 15, 2 ) NOT NULL AFTER  `col_total_pagado` ,
ADD  `col_total_recargos` FLOAT( 15, 2 ) NOT NULL AFTER  `col_cargos_vencidos` ,
ADD  `col_total_adeudo_vencido` FLOAT( 15, 2 ) NOT NULL AFTER  `col_total_recargos` ,
ADD  `col_total_adeudo_no_vencido` FLOAT( 15, 2 ) NOT NULL AFTER  `col_total_adeudo_vencido` ;
ALTER TABLE  `tbl_materias` ADD  `col_plan_estudios` INT( 11 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `tbl_materias` ADD  `col_creditos` FLOAT( 15, 2 ) NOT NULL ;
ALTER TABLE  `tbl_periodos` ADD  `col_transversal` INT( 11 ) NOT NULL DEFAULT  '0' AFTER  `col_club_lectura` ,
ADD  `col_plan_estudios` INT( 11 ) NOT NULL DEFAULT  '0' AFTER  `col_transversal` ;

CREATE TABLE IF NOT EXISTS `tbl_planes_estudios` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_nombre` varchar(200) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
ALTER TABLE `tbl_periodos`
  DROP `col_transversales`,
  DROP `col_academia`;


  /* 27/11/2018 */
  ALTER TABLE `tbl_materias`
  DROP `col_maestroid`,
  DROP `col_periodoid`;

CREATE TABLE IF NOT EXISTS `tbl_talleres` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_alumnoid` int(11) NOT NULL,
  `col_periodoid` int(11) NOT NULL,
  `col_materiaid` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `tbl_academias` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_alumnoid` int(11) NOT NULL,
  `col_periodoid` int(11) NOT NULL,
  `col_materiaid` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `tbl_transversales` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_alumnoid` int(11) NOT NULL,
  `col_periodoid` int(11) NOT NULL,
  `col_materiaid` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE  `tbl_alumnos` ADD  `col_plan_estudios` INT( 11 ) NOT NULL ;
ALTER TABLE  `tbl_users` ADD  `col_maestro` tinyint(2) NOT NULL DEFAULT '0' AFTER  `col_depto` ;


/* 28/11/2018 */
CREATE TABLE IF NOT EXISTS `tbl_bitacora_sesiones` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_usuarioid` int(11) NOT NULL,
  `col_tipo` tinyint(2) NOT NULL DEFAULT '0',
  `col_ip` varchar(50) NOT NULL,
  `col_fuente` varchar(100) NOT NULL,
  `col_fecha_entrada` date NOT NULL,
  `col_hora_entrada` varchar(10) NOT NULL,
  `col_fecha_salida` date NOT NULL,
  `col_hora_salida` varchar(10) NOT NULL,
  `col_ultimo_inicio` datetime NOT NULL,
  `col_ultima_ip` varchar(50) NOT NULL,
  `col_ultimo_device` varchar(100) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE  `tbl_alumnos` ADD  `col_estatus` ENUM(  'activo',  'baja' ) NOT NULL DEFAULT  'activo';
ALTER TABLE  `tbl_periodos` ADD  `col_transversal_alt` VARCHAR( 255 ) NOT NULL AFTER  `col_transversal` ;
ALTER TABLE  `tbl_config` ADD  `col_periodo` VARCHAR( 100 ) NOT NULL DEFAULT  '' AFTER  `col_postal` ;


/* 29/11/2018 */
ALTER TABLE `tbl_asistencia` DROP `col_grupoid`;
ALTER TABLE `tbl_asistencia` DROP `col_alumnos`;
ALTER TABLE `tbl_asistencia` ADD  `col_maestroid` INT( 11 ) NOT NULL AFTER  `col_materiaid` ;
ALTER TABLE  `tbl_asistencia` CHANGE  `col_fecha`  `col_fecha` DATE NULL DEFAULT NULL ;
ALTER TABLE  `tbl_asistencia` CHANGE  `col_created_id`  `col_created_at` DATETIME NULL DEFAULT NULL ;


CREATE TABLE IF NOT EXISTS `tbl_asistencia_alumnos` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_listaid` int(11) NOT NULL,
  `col_alumnoid` int(11) NOT NULL,
  `col_asistencia` enum('A','F','P','R') NOT NULL DEFAULT 'F',
  `col_participacion` int(11) NOT NULL DEFAULT '0',
  `col_comportamiento` enum('','ar','ve','ti','au','pe') NOT NULL DEFAULT '',
  `col_justificacion` varchar(255) NOT NULL DEFAULT '',
  `col_created_by` int(11) NOT NULL,
  `col_created_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


ALTER TABLE  `tbl_maestros_taxonomia` CHANGE  `col_materiaid`  `col_materia_clave` VARCHAR( 11 ) NOT NULL ;
ALTER TABLE  `tbl_calificaciones` CHANGE  `col_materiaid`  `col_materia_clave` VARCHAR( 11 ) NULL DEFAULT  '';

ALTER TABLE  `tbl_calificaciones` CHANGE  `col_p1`  `col_p1` VARCHAR( 10 ) NOT NULL ,
CHANGE  `col_p2`  `col_p2` VARCHAR( 10 ) NOT NULL ,
CHANGE  `col_ef`  `col_ef` VARCHAR( 10 ) NOT NULL ,
CHANGE  `col_cf`  `col_cf` VARCHAR( 10 ) NOT NULL ,
CHANGE  `col_ext`  `col_ext` VARCHAR( 10 ) NOT NULL ,
CHANGE  `col_ts`  `col_ts` VARCHAR( 10 ) NOT NULL ;

/* 04/12/2018 */
ALTER TABLE  `tbl_users` ADD  `col_password_lastchage` DATETIME NOT NULL ;
ALTER TABLE  `tbl_alumnos` ADD  `col_password_lastchage` DATETIME NOT NULL ;

/* 05/12/2018 */
CREATE TABLE IF NOT EXISTS `tbl_club_lectura` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_alumnoid` int(11) NOT NULL,
  `col_periodoid` int(11) NOT NULL,
  `col_materiaid` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `tbl_practicas` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_alumnoid` int(11) NOT NULL,
  `col_periodoid` int(11) NOT NULL,
  `col_carreraid` int(11) NOT NULL,
  `col_convenio` varchar(255) NOT NULL,
  `col_oficio` varchar(255) NOT NULL,
  `col_lugar` varchar(255) NOT NULL,
  `col_titular` varchar(255) NOT NULL,
  `col_jefe` varchar(255) NOT NULL,
  `col_area` varchar(255) NOT NULL,
  `col_direccion` varchar(255) NOT NULL,
  `col_telefono` varchar(255) NOT NULL,
  `col_created_at` datetime NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `tbl_servicio_social` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_alumnoid` int(11) NOT NULL,
  `col_periodoid` int(11) NOT NULL,
  `col_carreraid` int(11) NOT NULL,
  `col_oficio` varchar(255) NOT NULL,
  `col_lugar` varchar(255) NOT NULL,
  `col_titular` varchar(255) NOT NULL,
  `col_jefe` varchar(255) NOT NULL,
  `col_area` varchar(255) NOT NULL,
  `col_direccion` varchar(255) NOT NULL,
  `col_telefono` varchar(255) NOT NULL,
  `col_created_at` datetime NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `tbl_practicas_seguimiento` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_practicaid` int(11) NOT NULL,
  `col_forma` tinyint(2) NOT NULL DEFAULT '0',
  `col_observaciones` text NOT NULL,
  `col_created_at` datetime NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


/* 2018/12/07 */
ALTER TABLE  `tbl_alumnos` CHANGE  `col_control`  `col_control` VARCHAR( 20 ) NOT NULL ;
ALTER TABLE  `tbl_config` ADD  `col_periodoid` INT( 11 ) NOT NULL AFTER  `col_periodo` ;
ALTER TABLE  `tbl_periodos` ADD  `col_groupid` INT( 11 ) NOT NULL AFTER  `col_id` ;
ALTER TABLE  `tbl_alumnos_taxonomia` ADD  `col_groupid` INT( 11 ) NOT NULL AFTER  `col_periodoid` ;

CREATE TABLE IF NOT EXISTS `tbl_periodos_nombres` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_nombre` varchar(255) NOT NULL DEFAULT '',
  `col_created_at` datetime NOT NULL,
  `col_updated_at` datetime NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
ALTER TABLE  `tbl_calificaciones` ADD  `col_groupid` INT( 11 ) NOT NULL AFTER  `col_periodoid` ;



ALTER TABLE  `tbl_actividades` ADD  `col_tipo` TINYINT( 2 ) NOT NULL DEFAULT  '0' AFTER  `col_archivo_nombre`;
CREATE TABLE IF NOT EXISTS `tbl_actividades_tareas` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_alumnoid` int(11) NOT NULL,
  `col_actividadid` int(11) NOT NULL,
  `col_calificacion` float(15,2) NOT NULL,
  `col_archivo` varchar(255) NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_created_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


/* 2019 */

CREATE TABLE IF NOT EXISTS `tbl_materias_maestros_planeacion` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_maestroid` int(11) NOT NULL,
  `col_materiaid` int(11) NOT NULL,
  `col_periodoid` int(11) NOT NULL,
  `col_archivo` varchar(255) NOT NULL,
  `col_estatus` tinyint(2) NOT NULL DEFAULT '0',
  `col_created_at` datetime NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE  `tbl_config` ADD  `col_fecha_inicio` DATE NOT NULL AFTER  `col_periodoid` ;
ALTER TABLE  `tbl_periodos` ADD  `col_fecha_inicio` DATE NOT NULL AFTER  `col_plan_estudios` ;

ALTER TABLE  `tbl_actividades` ADD  `col_calificacion` FLOAT( 15, 2 ) NOT NULL AFTER  `col_tipo` ;
ALTER TABLE  `tbl_actividades` ADD  `col_clave_materia` VARCHAR( 10 ) NOT NULL DEFAULT  '' AFTER  `col_calificacion` ;
ALTER TABLE  `tbl_actividades` ADD  `col_periodoid` INT( 11 ) NOT NULL AFTER  `col_clave_materia` ;


/* Enero 7 2019 */
ALTER TABLE  `tbl_alumnos` ADD  `col_primer_acceso` DATE NOT NULL ;


/* Enero 8 2019 */
INSERT INTO `tbl_departamentos` (`col_id`, `col_nombre`, `col_abreviacion`, `col_permisos`, `col_created_at`, `col_created_by`, `col_updated_at`, `col_updated_by`) VALUES (NULL, 'Recepción', NULL, '1', NULL, NULL, NULL, NULL), (NULL, 'Cobranza', NULL, '1', NULL, NULL, NULL, NULL), (NULL, 'Dirección', NULL, '1', NULL, NULL, NULL, NULL);


/* Enero 11 2019 */


ALTER TABLE  `tbl_asistencia` ADD  `col_observaciones` TEXT NOT NULL AFTER  `col_fecha` ;

CREATE TABLE IF NOT EXISTS `tbl_preguntas` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_pregunta` varchar(255) NOT NULL,
  `col_tipo_respuesta` tinyint(2) NOT NULL DEFAULT '0',
  `col_categoria` tinyint(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=18 ;

INSERT INTO `tbl_preguntas` (`col_id`, `col_pregunta`, `col_tipo_respuesta`, `col_categoria`) VALUES
(1, '¿Presentó las reglas de sus clases y criterios de evaluación?', 0, 0),
(2, '¿Explicó la planeación y objetivo de la materia?', 0, 0),
(3, '¿Abarca todos los temas de la planeación?', 0, 0),
(4, '¿Demuestra dominio del tema?', 0, 0),
(5, '¿Relaciona la teoría con la práctica?', 0, 0),
(6, '¿Hace pase de lista?', 0, 0),
(7, '¿Es claro al explicar?', 0, 0),
(8, '¿Resuelve dudas?', 0, 0),
(9, '¿Realiza casos prácticos?', 0, 0),
(10, '¿Permite la participación y opinión de ideas diferentes?', 0, 0),
(11, '¿Hace uso de diferentes estrategias didacticas?', 0, 0),
(12, '¿Hace retroalimentación?', 0, 0),
(13, '¿Es puntual?', 0, 0),
(14, '¿Es imparcial?', 0, 0),
(15, 'Su imagen personal es:', 1, 0),
(16, 'Su vocabulario es:', 1, 0),
(17, 'Su conocimiento es:', 1, 0);


CREATE TABLE IF NOT EXISTS `tbl_respuestas_eva` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_preguntaid` int(11) NOT NULL,
  `col_respuesta` varchar(10) NOT NULL,
  `col_maestroid` int(11) NOT NULL,
  `col_evaluacionid` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE  `tbl_actividades` ADD  `col_ponderacion` VARCHAR( 255 ) NOT NULL AFTER  `col_tipo` ;


/* Enero 12 2019 */
ALTER TABLE  `tbl_periodos` DROP  `col_transversal_alt` ;
ALTER TABLE  `tbl_periodos` ADD  `col_modalidad` INT( 1 ) NOT NULL DEFAULT  '0' AFTER  `col_fecha_inicio` ;
CREATE TABLE IF NOT EXISTS `tbl_horarios` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_materiaid` int(11) NOT NULL,
  `col_periodoid` int(11) NOT NULL,
  `col_lunes` varchar(100) NOT NULL,
  `col_martes` varchar(100) NOT NULL,
  `col_miercoles` varchar(100) NOT NULL,
  `col_jueves` varchar(100) NOT NULL,
  `col_viernes` varchar(100) NOT NULL,
  `col_sabado` varchar(100) NOT NULL,
  `col_domingo` varchar(100) NOT NULL,
  `col_created_at` datetime NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
ALTER TABLE  `tbl_periodos` ADD  `col_aprobado` TINYINT( 1 ) NOT NULL DEFAULT  '0' AFTER  `col_modalidad` ;


/* Enero 14, 2019 */
ALTER TABLE  `tbl_biblioteca` ADD  `col_multa_pagada` TINYINT( 1 ) NOT NULL DEFAULT  '0' AFTER  `col_multa` ;
CREATE TABLE IF NOT EXISTS `tbl_altruista` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_group_periodoid` int(11) NOT NULL,
  `col_nombre` varchar(100) NOT NULL,
  `col_integrantes` text NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_created_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


/* Enero 16 2019 */
TRUNCATE TABLE `tbl_departamentos`;
INSERT INTO `tbl_departamentos` (`col_id`, `col_nombre`, `col_abreviacion`, `col_permisos`, `col_created_at`, `col_created_by`, `col_updated_at`, `col_updated_by`) VALUES
(2, 'Coordinacion Academica', NULL, 1, NULL, NULL, NULL, NULL),
(3, 'Contratación de Académicos', NULL, 1, NULL, NULL, NULL, NULL),
(4, 'Control escolar', NULL, 1, NULL, NULL, NULL, NULL),
(5, 'Extensión universitaria', NULL, 1, NULL, NULL, NULL, NULL),
(6, 'Biblioteca', NULL, 1, NULL, NULL, NULL, NULL),
(7, 'Académico de Academias de Investigación', NULL, 1, NULL, NULL, NULL, NULL),
(8, 'Académico de Talleres', NULL, 1, NULL, NULL, NULL, NULL),
(9, 'Recepción', NULL, 1, NULL, NULL, NULL, NULL),
(10, 'Cobranza', NULL, 1, NULL, NULL, NULL, NULL),
(11, 'Dirección', NULL, 1, NULL, NULL, NULL, NULL),
(12, 'Encargada del sistema', NULL, 1, '2019-01-16 00:00:00', 1, '2019-01-16 00:00:00', 1),
(13, 'Contraloria', NULL, 1, '2019-01-16 00:00:00', 1, '2019-01-16 00:00:00', 1),
(14, 'Administración', NULL, 1, '2019-01-16 00:00:00', 1, '2019-01-16 00:00:00', 1),
(15, 'Caja', NULL, 1, '2019-01-16 00:00:00', 1, '2019-01-16 00:00:00', 1);

ALTER TABLE  `tbl_config` ADD  `col_periodo_cuatri` INT( 11 ) NOT NULL DEFAULT  '0' AFTER  `col_periodo` ,
ADD  `col_periodo_maestria` INT( 11 ) NOT NULL DEFAULT  '0' AFTER  `col_periodo_cuatri` ,
ADD  `col_periodo_doctorado` INT( 11 ) NOT NULL DEFAULT  '0' AFTER  `col_periodo_maestria` ;
ALTER TABLE  `tbl_asistencia_alumnos` ADD  `col_segunda` TINYINT( 1 ) NOT NULL DEFAULT  '0' AFTER  `col_justificacion` ;


/* Enero 17, 2019 */
CREATE TABLE IF NOT EXISTS `tbl_practicas_archivos` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_alumnoid` int(11) NOT NULL,
  `col_practicaid` int(11) NOT NULL,
  `col_archivo` varchar(255) NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_created_at` datetime NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `tbl_servicio_social_archivos` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_alumnoid` int(11) NOT NULL,
  `col_servicioid` int(11) NOT NULL,
  `col_archivo` varchar(255) NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_created_at` datetime NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE  `tbl_alumnos` ADD  `col_proce_prepa_estado` VARCHAR( 50 ) NOT NULL ,
ADD  `col_proce_licenciatura_estado` VARCHAR( 50 ) NOT NULL ,
ADD  `col_proce_maestria_estado` VARCHAR( 50 ) NOT NULL ,
ADD  `col_fecha_baja` DATE NOT NULL ;
ALTER TABLE  `tbl_alumnos` ADD  `col_documentos_otros` TEXT NOT NULL ,
ADD  `col_documentos_observaciones` TEXT NOT NULL ;

CREATE TABLE IF NOT EXISTS `tbl_altruista_asistencia` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_actividad` int(11) NOT NULL,
  `col_alumnoid` int(11) NOT NULL,
  `col_asistencia` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `tbl_altruista_actividades` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_grupo` int(11) NOT NULL,
  `col_group_periodoid` int(11) NOT NULL,
  `col_titulo` varchar(200) NOT NULL,
  `col_descripcion` text NOT NULL,
  `col_fecha` date NOT NULL,
  `col_created_at` datetime NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `tbl_altruista_integrantes` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_group_periodoid` int(11) NOT NULL,
  `col_grupo` int(11) NOT NULL,
  `col_alumnoid` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE  `tbl_altruista` CHANGE  `col_integrantes`  `col_grupo` INT( 11 ) NOT NULL ;
ALTER TABLE  `tbl_carreras` ADD  `col_estatus` TINYINT( 1 ) NOT NULL DEFAULT  '0' AFTER  `col_tipo` ;


CREATE TABLE `tbl_enlinea` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_email` varchar(200) NOT NULL,
  `col_time` int(20) NOT NULL,
  `col_ip` varchar(100) NOT NULL,
  PRIMARY KEY (`col_id`),
  UNIQUE KEY `col_email` (`col_email`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

ALTER TABLE  `tbl_servicio_social_archivos` ADD  `col_estatus` TINYINT( 1 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `tbl_practicas_archivos` ADD  `col_estatus` TINYINT( 1 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `tbl_practicas_archivos` ADD  `col_next` DATE NOT NULL ;
ALTER TABLE  `tbl_servicio_social_archivos` ADD  `col_next` DATE NOT NULL ;
ALTER TABLE  `tbl_servicio_social_archivos` ADD  `col_until` DATE NOT NULL ;


/* Enero 26 */
ALTER TABLE  `tbl_config` ADD  `col_primer_parcial_semestral` VARCHAR( 100 ) NOT NULL AFTER  `col_fecha_inicio` ,
ADD  `col_primer_parcial_cuatrimestral` VARCHAR( 100 ) NOT NULL AFTER  `col_primer_parcial_semestral` ,
ADD  `col_primer_parcial_maestria` VARCHAR( 100 ) NOT NULL AFTER  `col_primer_parcial_cuatrimestral` ,
ADD  `col_primer_doctorado` VARCHAR( 100 ) NOT NULL AFTER  `col_primer_parcial_maestria` ,
ADD  `col_segundo_parcial_semestral` VARCHAR( 100 ) NOT NULL AFTER  `col_primer_doctorado` ,
ADD  `col_segundo_parcial_cuatrimestral` VARCHAR( 100 ) NOT NULL AFTER  `col_segundo_parcial_semestral` ,
ADD  `col_segundo_parcial_maestria` VARCHAR( 100 ) NOT NULL AFTER  `col_segundo_parcial_cuatrimestral` ,
ADD  `col_segundo_parcial_doctorado` VARCHAR( 100 ) NOT NULL AFTER  `col_segundo_parcial_maestria` ;

ALTER TABLE  `tbl_config` ADD  `col_candados_asistencias` TINYINT( 1 ) NOT NULL DEFAULT  '1' AFTER  `col_segundo_parcial_doctorado` ;
ALTER TABLE  `tbl_config` ADD  `col_reportes_servicio_social` TINYINT( 1 ) NOT NULL DEFAULT  '0' AFTER  `col_candados_asistencias` ;
ALTER TABLE  `tbl_config` ADD  `col_reportes_practicas` TINYINT( 1 ) NOT NULL DEFAULT  '0' AFTER  `col_reportes_servicio_social` ;


CREATE TABLE IF NOT EXISTS `tbl_eva_maestros` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_group_periodoid` int(11) NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_created_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `tbl_eva_maestros_observaciones` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_evaid` int(11) NOT NULL,
  `col_maestroid` int(11) NOT NULL,
  `col_alumnoid` int(11) NOT NULL,
  `col_observaciones` text NOT NULL,
  `col_created_at` datetime NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `tbl_eva_maestros_preguntas` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_evaid` int(11) NOT NULL,
  `col_pregunta` varchar(255) NOT NULL,
  `col_respuesta` tinyint(1) NOT NULL DEFAULT '0',
  `col_tipo_maestro` enum('regular','taller','academia') NOT NULL DEFAULT 'regular',
  `col_created_at` datetime NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `tbl_eva_maestros_respuestas` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_evaid` int(11) NOT NULL,
  `col_preguntaid` int(11) NOT NULL,
  `col_maestroid` int(11) NOT NULL,
  `col_alumnoid` int(11) NOT NULL,
  `col_respuesta` varchar(20) NOT NULL,
  `col_aprobado` tinyint(1) NOT NULL DEFAULT '0',
  `col_created_at` datetime NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


ALTER TABLE  `tbl_eva_maestros_observaciones` ADD  `col_estatus` TINYINT( 1 ) NOT NULL DEFAULT  '0' AFTER  `col_observaciones` ;
ALTER TABLE  `tbl_eva_maestros_respuestas` ADD  `col_materiaid` INT( 11 ) NOT NULL AFTER  `col_maestroid` ;
ALTER TABLE  `tbl_eva_maestros_observaciones` ADD  `col_materiaid` INT( 11 ) NOT NULL AFTER  `col_maestroid`
ALTER TABLE  `tbl_eva_maestros_preguntas` CHANGE  `col_tipo_maestro`  `col_tipo_maestro` ENUM(  'regular',  'taller',  'academia',  'club' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'regular';

/* Febrero 6, 2019 */
ALTER TABLE  `tbl_practicas_archivos` ADD  `col_comentarios` TEXT NOT NULL ;

/* Febrero 12, 2019 */
ALTER TABLE  `tbl_servicio_social_archivos` ADD  `col_comentarios` TEXT NOT NULL ;

/* Febrero 19, 2019 */
ALTER TABLE  `tbl_eva_maestros` DROP  `col_para` ;
ALTER TABLE  `tbl_eva_maestros` ADD  `col_para` TINYINT( 2 ) NOT NULL DEFAULT  '0' AFTER  `col_estatus` ;
ALTER TABLE  `tbl_eva_maestros` ADD  `col_especificos` TEXT NOT NULL AFTER  `col_para` ;


/* Febrero 22, 2019 */
ALTER TABLE  `tbl_actividades` ADD  `col_materiaid` INT( 0 ) NOT NULL AFTER  `col_tipo` ;

/* Febrero 27, 2019 */
CREATE TABLE IF NOT EXISTS `tbl_academias_observaciones` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_materiaid` int(11) NOT NULL,
  `col_maestroid` int(11) NOT NULL,
  `col_alumnoid` int(11) NOT NULL,
  `col_observaciones` text NOT NULL,
  `col_created_at` datetime NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/* Febrero 28, 2019 */
CREATE TABLE IF NOT EXISTS `tbl_mensajes` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_senderid` int(11) NOT NULL,
  `col_tipo` varchar(50) NOT NULL,
  `col_asunto` varchar(255) NOT NULL,
  `col_texto` text NOT NULL,
  `col_reply` varchar(255) NOT NULL,
  `col_destinatarios` text NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_created_at` datetime NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `tbl_mensajes_adjuntos` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_mensajeid` int(11) NOT NULL,
  `col_adjunto` varchar(255) NOT NULL,
  `col_adjunto_nombre` varchar(255) NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_created_at` datetime NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE  `tbl_mensajes` ADD  `col_destinatarios_form` VARCHAR( 255 ) NOT NULL AFTER  `col_destinatarios` ;

/* Marzo 1, 2019 */
ALTER TABLE  `tbl_actividades_tareas` ADD  `col_falsificacion` TINYINT( 1 ) NOT NULL DEFAULT  '0' AFTER  `col_calificacion` ;

CREATE TABLE IF NOT EXISTS `tbl_eva_alumnos` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_titulo` varchar(200) NOT NULL,
  `col_group_periodoid` int(11) NOT NULL,
  `col_estatus` tinyint(1) NOT NULL DEFAULT '0',
  `col_para` tinyint(2) NOT NULL DEFAULT '0',
  `col_especificos` text NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_created_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `tbl_eva_alumnos_preguntas` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_evaid` int(11) NOT NULL,
  `col_pregunta` varchar(255) NOT NULL,
  `col_respuesta_visual` varchar(255) NOT NULL,
  `col_respuesta_auditiva` varchar(255) NOT NULL,
  `col_respuesta_cinestesica` varchar(255) NOT NULL,
  `col_created_at` datetime NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `tbl_eva_alumnos_respuestas` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_evaid` int(11) NOT NULL,
  `col_preguntaid` int(11) NOT NULL,
  `col_alumnoid` int(11) NOT NULL,
  `col_respuesta` varchar(20) NOT NULL,
  `col_aprobado` tinyint(1) NOT NULL DEFAULT '0',
  `col_created_at` datetime NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/* Marzo 7, 2019 */
CREATE TABLE IF NOT EXISTS `tbl_alumnos_bajas` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_alumnoid` int(11) NOT NULL,
  `col_fecha_baja` datetime NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE  `tbl_calificaciones` ADD INDEX (  `col_alumnoid` ) ;


/* Marzo 7, 2019 */
CREATE TABLE IF NOT EXISTS `tbl_padres_familia` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_alumnoid` int(11) NOT NULL,
  `col_nombre` varchar(255) NOT NULL,
  `col_correo` varchar(255) NOT NULL,
  `col_password` varchar(255) NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_created_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  PRIMARY KEY (`col_id`),
  KEY `col_alumnoid` (`col_alumnoid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/* Marzo 13, 2019 */
ALTER TABLE  `tbl_maestros` ADD  `col_edit_calificaciones` TINYINT( 1 ) NOT NULL DEFAULT  '0';
/* Marzo 14, 2019 */
ALTER TABLE  `tbl_maestros` ADD  `col_edit_asistencias` TINYINT( 1 ) NOT NULL DEFAULT  '0';


/* Marzo 25, 2019 */
ALTER TABLE  `tbl_calificaciones` ADD  `col_estatus` TINYINT( 2 ) NOT NULL DEFAULT  '0' AFTER  `col_updated_by` ;
UPDATE `tbl_calificaciones` SET col_estatus=1 WHERE 1=1
ALTER TABLE  `tbl_config` ADD  `col_calificaciones_estatus` TINYINT( 2 ) NOT NULL DEFAULT  '1',
ADD  `col_correos_cumpleanos` VARCHAR( 255 ) NOT NULL ,
ADD  `col_multa_biblioteca` FLOAT( 15, 2 ) NOT NULL DEFAULT  '0.00';
UPDATE  tbl_config SET  col_correos_cumpleanos='direccioninef@inef.com.mx, academicolicenciatura@fldch.edu.mx' WHERE  col_id=1;

/* Marzo 28, 2019 */
ALTER TABLE  `tbl_actividades_tareas` ADD  `col_sd` TINYINT( 1 ) NOT NULL DEFAULT  '0' AFTER  `col_falsificacion` ;
ALTER TABLE  `tbl_actividades_tareas` ADD  `col_sd_razon` VARCHAR( 255 ) NOT NULL AFTER  `col_sd` ;
ALTER TABLE  `tbl_actividades_tareas` ADD  `col_sdme` TINYINT( 1 ) NOT NULL DEFAULT  '0' AFTER  `col_sd_razon` ;
ALTER TABLE  `tbl_actividades_tareas` ADD  `col_sdme_razon` VARCHAR( 255 ) NOT NULL AFTER  `col_sdme` ;
ALTER TABLE  `tbl_actividades_tareas` CHANGE  `col_sdme`  `col_sdme` VARCHAR( 10 ) NOT NULL DEFAULT  'A';

/* Abril 2, 2019 */
CREATE TABLE IF NOT EXISTS `tbl_atencion` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_alumnoid` int(11) NOT NULL,
  `col_asunto` varchar(255) NOT NULL,
  `col_observaciones` text NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_created_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  PRIMARY KEY (`col_id`),
  KEY `col_alumnoid` (`col_alumnoid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `tbl_seguimiento` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_alumnoid` int(11) NOT NULL,
  `col_razones` text NOT NULL,
  `col_asignado` int(11) NOT NULL,
  `col_estatus` tinyint(1) NOT NULL DEFAULT '0',
  `col_created_at` datetime NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `tbl_seguimiento_observaciones` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_seguimientoid` int(11) NOT NULL,
  `col_observaciones` text NOT NULL,
  `col_created_at` datetime NOT NULL,
  `col_created_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/* Abril 3, 2019 */
ALTER TABLE  `tbl_atencion` ADD  `col_estatus` TINYINT( 1 ) NOT NULL DEFAULT  '0' AFTER  `col_observaciones` ;
ALTER TABLE  `tbl_seguimiento` ADD  `col_tipo` VARCHAR( 150 ) NOT NULL DEFAULT  '' AFTER  `col_razones` ;

/* Abril 4, 2019 */
ALTER TABLE  `tbl_atencion` ADD  `col_tipo` TINYINT( 2 ) NOT NULL DEFAULT  '0' AFTER  `col_estatus` ;
ALTER TABLE  `tbl_atencion` ADD  `col_fecha` DATE NOT NULL AFTER  `col_tipo` ,
ADD  `col_hora_entrada` VARCHAR( 30 ) NOT NULL DEFAULT  '' AFTER  `col_fecha` ,
ADD  `col_hora_salida` VARCHAR( 30 ) NOT NULL DEFAULT  '' AFTER  `col_hora_entrada` ,
ADD  `col_fecha_cita` DATE NOT NULL AFTER  `col_hora_salida` ;
ALTER TABLE  `tbl_atencion` ADD  `col_firma_userid` INT( 11 ) NOT NULL AFTER  `col_fecha_cita` ;


/* Abril 5, 2019 */
ALTER TABLE  `tbl_atencion` ADD  `col_hora_cita` VARCHAR( 11 ) NOT NULL DEFAULT  '' AFTER  `col_fecha_cita` ;

ALTER TABLE  `tbl_practicas` ADD  `col_cargo_titular` VARCHAR( 255 ) NOT NULL AFTER  `col_titular` ;
ALTER TABLE  `tbl_servicio_social` ADD  `col_cargo_titular` VARCHAR( 255 ) NOT NULL AFTER  `col_titular` ;
ALTER TABLE  `tbl_config` ADD  `col_encargado_control_escolar` VARCHAR( 255 ) NOT NULL AFTER  `col_multa_biblioteca` ,
ADD  `col_correo_practicas` VARCHAR( 255 ) NOT NULL AFTER  `col_encargado_control_escolar` ;


/* Mayo 9, 2019 */

ALTER TABLE  `tbl_biblioteca` ADD  `col_renovacion_count` TINYINT( 2 ) NOT NULL DEFAULT  '0';

/* Mayo 10, 2019 */

CREATE TABLE IF NOT EXISTS `tbl_representantes` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_alumnoid` int(11) NOT NULL,
  `col_correo` varchar(150) NOT NULL,
  `col_password` varchar(255) NOT NULL,
  `col_ultimo_acceso` datetime NOT NULL,
  `col_updated_by` int(11) NOT NULL,
  `col_updated_at` datetime NOT NULL,
  PRIMARY KEY (`col_id`),
  KEY `col_alumnoid` (`col_alumnoid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE  `tbl_menu` ADD  `col_representante` TINYINT( 2 ) NOT NULL DEFAULT  '0' AFTER  `col_admins` ;

ALTER TABLE  `tbl_alumnos` CHANGE  `col_estatus`  `col_estatus` ENUM(  'activo',  'baja',  'bajatemporal' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT  'activo';
ALTER TABLE  `tbl_atencion` ADD  `col_vigente` VARCHAR( 255 ) NOT NULL ,
ADD  `col_turno` VARCHAR( 255 ) NOT NULL ,
ADD  `col_ampara` VARCHAR( 255 ) NOT NULL ,
ADD  `col_jefe_servicios_escolares` VARCHAR( 255 ) NOT NULL ,
ADD  `col_director_educacion` VARCHAR( 255 ) NOT NULL ,
ADD  `col_jefe_oficina` VARCHAR( 255 ) NOT NULL ,
ADD  `col_subsecretario` VARCHAR( 255 ) NOT NULL ,
ADD  `col_numero` VARCHAR( 255 ) NOT NULL ,
ADD  `col_libro` VARCHAR( 255 ) NOT NULL ,
ADD  `col_foja` VARCHAR( 255 ) NOT NULL ;
ALTER TABLE  `tbl_atencion` ADD  `col_fecha_depto_escolares` VARCHAR( 255 ) NOT NULL ;

ALTER TABLE  `tbl_planes_estudios` ADD  `col_descripcion` VARCHAR( 255 ) NOT NULL ,
ADD  `col_archivo` VARCHAR( 255 ) NOT NULL ,
ADD  `col_actualizacion` DATE NOT NULL ,
ADD  `col_updated_by` INT( 11 ) NOT NULL ,
ADD  `col_updated_at` DATETIME NOT NULL ;

CREATE TABLE IF NOT EXISTS `tbl_catalogo_reportes` (
  `col_id` int(11) NOT NULL AUTO_INCREMENT,
  `col_nombre` varchar(255) NOT NULL,
  `col_url` varchar(255) NOT NULL,
  `col_estatus` tinyint(2) NOT NULL DEFAULT '1',
  `col_permisos` varchar(255) NOT NULL,
  PRIMARY KEY (`col_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 ;

INSERT INTO `tbl_catalogo_reportes` (`col_id`, `col_nombre`, `col_url`, `col_estatus`, `col_permisos`) VALUES
(1, 'Reportes SD por M.E', 'reporteSDME', 1, ''),
(2, 'Inventario de documentos alumnos', 'inventarioDocumentos', 1, ''),
(3, 'Reportes de alumnos reprobados', 'alumnosReprobados', 1, ''),
(4, 'Reporte de Extraordinarios', 'reporteExtraordinarios', 1, ''),
(5, 'Reporte de Titulo Suficiencia', 'reporteTS', 1, ''),
(6, 'Reporte de bajas de alumnos', 'reporteBajasAlumnos', 1, ''),
(7, 'Reporte de Modelo Educativo', 'reporteME', 1, ''),
(8, 'Control de tramites de titulación', 'reporteTramitesTitulacion', 1, ''),
(9, 'Credenciales alumnos ', 'credencialesAlumnos', 1, '');



ALTER TABLE  `tbl_users` ADD  `col_titulo` VARCHAR( 50 ) NOT NULL AFTER  `col_pass` ;
ALTER TABLE  `tbl_atencion` ADD  `col_fecha_vigencia` DATE NOT NULL ;


ALTER TABLE  `tbl_atencion` ADD  `col_duplicado` TINYINT( 2 ) NOT NULL DEFAULT  '0';
ALTER TABLE  `tbl_departamentos` ADD  `col_nombre_puesto` VARCHAR( 255 ) NOT NULL AFTER  `col_nombre` ;
ALTER TABLE  `tbl_departamentos` ADD  `col_responsableid` INT( 11 ) NOT NULL AFTER  `col_nombre` ;


ALTER TABLE  `tbl_academias` ADD  `col_created_at` DATETIME NOT NULL ,
ADD  `col_created_by` INT( 11 ) NOT NULL ,
ADD  `col_updated_at` DATETIME NOT NULL ,
ADD  `col_updated_by` INT( 11 ) NOT NULL ;

ALTER TABLE  `tbl_talleres` ADD  `col_created_at` DATETIME NOT NULL ,
ADD  `col_created_by` INT( 11 ) NOT NULL ,
ADD  `col_updated_at` DATETIME NOT NULL ,
ADD  `col_updated_by` INT( 11 ) NOT NULL ;


ALTER TABLE  `tbl_alumnos_taxonomia` ADD  `col_baja` TINYINT( 2 ) NOT NULL DEFAULT  '0' AFTER  `col_status` ;


ALTER TABLE `tbl_config` ADD `col_mtop` INT( 5 ) NOT NULL DEFAULT '35',
ADD `col_mbottom` INT( 5 ) NOT NULL DEFAULT '35',
ADD `col_mright` INT( 5 ) NOT NULL DEFAULT '20',
ADD `col_mleft` INT( 5 ) NOT NULL DEFAULT '20',
ADD `col_mtop_alt` INT( 5 ) NOT NULL DEFAULT '45',
ADD `col_mbottom_alt` INT( 5 ) NOT NULL DEFAULT '35',
ADD `col_mright_alt` INT( 5 ) NOT NULL DEFAULT '10',
ADD `col_mleft_alt` INT( 5 ) NOT NULL DEFAULT '10';

ALTER TABLE `tbl_config` ADD `col_mtop_cert` INT( 5 ) NOT NULL DEFAULT '35',
ADD `col_mbottom_cert` INT( 5 ) NOT NULL DEFAULT '35',
ADD `col_mright_cert` INT( 5 ) NOT NULL DEFAULT '20',
ADD `col_mleft_cert` INT( 5 ) NOT NULL DEFAULT '20';