<?php
require_once ("vendor/autoload.php");
use Systemico\JMail;
/* leer el archivo de configuración */
$configuracion = parse_ini_file(".email.conf");
$limite_mensajes = $configuracion['limite_mensajes'];
/* verificar que IMAP está configurado en el servidor actual */
if (! function_exists('imap_open')) {
	echo "IMAP no está configurado.";
	die();
} else {
	/* Conectando al servidor de correo mediante IMAP */
	$conexion = imap_open('{'.$configuracion['servidor_correo'].'}INBOX',
						$configuracion['usuario_correo'],
						$configuracion['clave_correo']) 
						or die('No se puede conectar al servidor de correo' . imap_last_error());
	
	/* Search Emails having the specified keyword in the email subject */
	$bandeja_entrada = imap_search($conexion, 'ALL');
	/* verificar que la bandeja de entrada contenga correos */
	if (! empty($bandeja_entrada)) {
		/* asignar los correos limite que se leeran */
		$cantidad_mensajes = count($bandeja_entrada);
		/* verificar que $limite_mensajes sea menor que $cantidad_mensajes */
		if( $cantidad_mensajes - $limite_mensajes > 0 ){
			$limite = $cantidad_mensajes - $limite_mensajes;
		}else{
			$limite = 0;
			$limite_mensajes = $cantidad_mensajes;
		}
		/* inicializacion de JMail */
		$jmail= new JMail();
		$jmail->credentials($configuracion['API_KEY'], $configuracion['dominio_mailgun'], $configuracion['correo_remitente'],JMail::$MAILGUN,$configuracion['nombre_remitente']);
		/* ciclo de lectura de los correos más recientes a los más antiguos limitado por $limite_mensajes */
		$j= 1;
		for($i=$cantidad_mensajes-1; $i>=$limite; $i=$i-1)
		{
			echo "procesando $j de $limite_mensajes". PHP_EOL;
			$cabecera = imap_header($conexion, $bandeja_entrada[$i], 0);
			/* se recupera la dirección de correo de esta manera para evadir diple <> */
			$correo_destinatario = $cabecera->from[0]->mailbox . "@" . $cabecera->from[0]->host;
			/* enviar el correo de respuesta automatica mediante JMail */
			$jmail->send($correo_destinatario,
				'Respuesta automática al correo '.$cabecera->subject,
				'Respuesta automática al correo '.$cabecera->subject);
			echo 'correo procesado '.$cabecera->subject. PHP_EOL;
		}
	}
	imap_close($conexion);
}
	
