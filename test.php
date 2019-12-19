<?php
header("Content-Type: text/html; charset=utf-8");
//require('../../../fpdf/fpdf.php');
require('../fpdf17/pdf_mc_table.php');
include_once("../../libfinanzas/inc_readme.php");
$link = conectarse_Finanzas();
session_start();
require('../Classes/class.phpmailer.php');

$txt_rut = $_REQUEST['txt_rut'];
$dv = $_REQUEST['dv'];
$DESC_CCOSTO = $_REQUEST['DESC_CCOSTO'];
$nombres = $_REQUEST['nombres'];
$cv_fecha_inicio = $_REQUEST['cv_fecha_inicio'];
$cv_fecha_termino = $_REQUEST['cv_fecha_termino'];
$descripcion = $_REQUEST['descripcion'];

$nr_work_days = getWorkingDays($cv_fecha_inicio, $cv_fecha_termino);

$cv_fecha_inicio = date('d/m/Y',strtotime($cv_fecha_inicio));
$cv_fecha_termino = date('d/m/Y',strtotime($cv_fecha_termino));



function getWorkingDays($startDate, $endDate)
{
    $begin = strtotime($startDate);
    $end   = strtotime($endDate);
    if ($begin > $end) {
        echo "startdate is in the future! <br />";

        return 0;
    } else {
        $no_days  = 0;
        $weekends = 0;
        while ($begin <= $end) {
            $no_days++; // no of days in the given interval
            $what_day = date("N", $begin);
            if ($what_day > 5) { // 6 and 7 are weekend days
                $weekends++;
            };
            $begin += 86400; // +1 day
        };
        $working_days = $no_days - $weekends;

        return $working_days;
    }
}


$pdf = new PDF_MC_Table('P', 'mm', 'letter');
//add page, set font
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetMargins(9, 22 , 5);

$pdf->SetX(10);
$pdf->SetY(9);
$pdf->SetFont('Arial','B',8);
$pdf->SetTextColor(0,0,0);
$pdf->Image('../img/logouct2.jpg', 10, 10, 35);
$pdf->SetX(50);
$pdf->SetY(12);
$pdf->Ln(5);
$pdf->Cell(200,20,utf8_decode('SOLICITUD DE SUSPENSIÓN'),0,0,'C');
$pdf->SetX(70);
$pdf->SetY(12);
$pdf->Ln(5);
$pdf->Ln(10);
$pdf->Cell(0,29,utf8_decode('Módulo de beneficio de colación'),0,0,'L');
$pdf->SetTextColor(0,0,0);
$pdf->SetFont('Arial','',8);
$pdf->Cell(-5,28,utf8_decode('Fecha de emisión:').' '.date("d/m/Y"),0,0,'R');
$pdf->SetY(10);
$pdf->Ln(40);
$pdf->SetFont('Arial','',12);
// $pdf->Cell(0,0,''.$titulo.'',0,0,'C');
$pdf->Ln(7);
$pdf->SetFont('Arial','',8);

$pdf->SetWidths(Array(30,80,90));

//set alignment
$pdf->SetAligns(Array('L','L','L'));

//set line height. This is the height of each lines, not rows.
$pdf->SetLineHeight(5);


$pdf->SetFont('Arial','',8);
$pdf->SetFillColor(221,212,210);
$pdf->Cell(30,5,"Rut",1,0,'C','true');
$pdf->Cell(80,5,"Nombres",1,0,'C','true');
$pdf->Cell(90,5,"Unidad",1,0,'C','true');

$pdf->Ln();


$pdf->Row(Array(
    $txt_rut.'-'.$dv,
    utf8_decode($nombres),
    utf8_decode($DESC_CCOSTO),
));

$pdf->Ln(10);
$pdf->SetWidths(Array(30,30,30,110));

//set alignment
$pdf->SetAligns(Array('C','L','C'));

//set line height. This is the height of each lines, not rows.
$pdf->SetLineHeight(5);


$pdf->SetFont('Arial','',8);
$pdf->SetFillColor(221,212,210);
$pdf->Cell(30,5,"Fecha inicio",1,0,'L','true');
$pdf->Cell(30,5,utf8_decode("Fecha término"),1,0,'L','true');
$pdf->Cell(30,5,utf8_decode("Días suspensión"),1,0,'L','true');
$pdf->Cell(110,5,"Motivo",1,0,'L','true');

$pdf->Ln();

$pdf->Row(Array(
    $cv_fecha_inicio,
    $cv_fecha_termino,
    $nr_work_days,
    utf8_decode($descripcion),
));

$pdf->Ln(10);
$pdf->SetWidths(Array(110));

//set alignment
$pdf->SetAligns(Array('C','L','C'));

//set line height. This is the height of each lines, not rows.
$pdf->SetLineHeight(5);


$pdf->SetFont('Arial','',8);
$pdf->SetFillColor(221,212,210);
$pdf->Cell(110,5,utf8_decode("Usuario que registró suspensión"),1,0,'L','true');

$pdf->Ln();

$pdf->Row(Array(
    $_SESSION["_UNOMBRE"],
  
));


$pdf->SetTopMargin(120);

$file = $pdf->output("json/pdf.pdf","S");
$file_name = md5(rand()) . '.pdf';
 file_put_contents($file_name, $file);


$addcc='';


$mail = new PHPMailer(true);


try {
    $link = conectarse_Finanzas();
    $txt_rut = $_REQUEST['txt_rut'];
    $dv = $_REQUEST['dv'];
    $rut_completo=$txt_rut.'-'.$dv;

    $sqlrut = "select FUNC_RUT, FUNC_EMAIL from COMUN.dbo.funcionarios where FUNC_RUT='$rut_completo'"; 
    $row_correo=mssql_query($sqlrut,$link);
    $correo = mssql_fetch_array($row_correo);

    $corre_url_solicitud=$correo['FUNC_EMAIL'];

        $mail->isMail();
        $mail->setFrom('uctemuco@uct.cl', utf8_decode('Universidad Católica de Temuco'));
        $mail->AddAddress($corre_url_solicitud);

        if($_SESSION['user'] == $txt_rut){
            //$addcc='';
        }
        
        else{
            
            $sqlrut = "select FUNC_RUT, FUNC_EMAIL from COMUN.dbo.funcionarios where FUNC_RUT='".rtrim($_SESSION['user'])."'"; 
            $row_correo=mssql_query($sqlrut,$link);
            $correo = mssql_fetch_array($row_correo);
            $addcc=rtrim($correo['FUNC_EMAIL']);
            $mail->AddCC($addcc);
            
        }  
        $mail->msgHTML(utf8_decode('Hemos recepcionado su suspensión beneficio colación y se ha notificado al concesionario para que realice rebaja correspondiente.
        Desde ya agradecemos su colaboración. 
        <br><br><br><br><br>
        <span>Dirección Desarrollo de Personas</span><br>
        <span>Vicerrectoría de Administración y Asuntos Económicos</span><br>
        <span>Universidad Católica de Temuco</span><br>
        <span>Campus Luis Rivas del Canto</span><br>
        <span>Rudecindo Ortega 03694, Edificio VRAE</span><br>
        <span>Temuco, Chile.</span><br>'));							
        $mail->addStringAttachment($file, 'ficha_solicitud.pdf','base64','application/pdf'); 
        $mail->Subject = utf8_decode('Solicitud suspensión almuerzo');		

        $mail->Send();			

} catch (phpmailerException $e) {
    echo $e->errorMessage(); 
} catch (Exception $e) {
    echo $e->getMessage(); 
}


unlink($file_name); 
?>