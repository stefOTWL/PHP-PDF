<?php
include_once('fpdf.php');

$orientation = 'L'; //Page Orientation
$pageSize = 'A3';   //Page Size

class PDF extends FPDF
{
    // Page header
    function Header()
    {   
        //Page Headers
        $pageHeaders = array('Header 1', 'Header 2', 'Header 3');
        $pdf = new PDF();
        $w = $pdf->GetPageWidth();
        $this->SetFont('Arial','',8);
        $this->Ln();
        $this->SetFont('Arial','B',13);
        // Title
        for($i=0; $i<sizeof($pageHeaders); $i++){
            $this->Cell('',8,$pageHeaders[$i].'',0,0,'C');
            $this->Ln(15);
        }
    }
    
    // Page footer
    function Footer()
    {   
        $pdf = new PDF();
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        $today = date("l jS F Y h:i A");
        // Arial italic 8
        $this->SetFont('Arial','I',8);
        // Page number
        $w = $pdf->GetPageWidth();
        // $this->Cell($w,10, $w.'', 1,0,'L');
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'L');
        $this->Cell('',10,'Run date: '.$today,0,0,'R');
    }
    }

    $connection = mysqli_connect("localhost", "root", "password123", "test");
    if(!$connection){
        die("Query Failed");
    }
    // $display_heading = array('id'=>'ID', 'employee_name'=> 'Name', 'employee_age'=> 'Age','employee_salary'=> 'Salary',);
    $tableinfo = array();
    $sum = 0;
    
    $result = mysqli_query($connection, "SELECT * FROM demo");
    $row = mysqli_fetch_fields($result);
    foreach($row as $val){
        $tableinfo[] = $val->name;
        $result2 = mysqli_query($connection, 'SELECT MAX(LENGTH('.$val->name.')) FROM demo;');
        while($row2 = mysqli_fetch_row($result2)){
            $colInfo[] = $row2;
            foreach ($row2 as $item) {
                    $sum += $item;
            }
        }
    }
    for($i=0; $i<sizeof($colInfo);$i++){
         $colInfo[$i] = $colInfo[$i][0]/$sum;
    }
    
    $pdf = new PDF($orientation, 'mm', $pageSize);
    
    //header
    $pdf->AddPage();
    $pdf->SetDrawColor(175, 177, 181);
    
    //foter page
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',12);
    // $w = $pdf->GetPageWidth();
    $w = ($pdf->GetPageWidth()-28.35);
    $i=0;    
    foreach($tableinfo as $heading) {
        $width = ($w * $colInfo[$i]);
        $pdf->Cell($width,15,$heading,1, 0, 'C');
        $i++;
    }
    
    foreach($result as $row) {
        $pdf->Ln();
        $j=0;
        foreach($row as $column){
            $width = ($w * $colInfo[$j]);
            $pdf->Cell($width, 12, $column, 1, 0, 'C');
            $j++;
        }
         
    }
    $pdf->Output('', 'Test.pdf');
?>