<?php
header("Access-Control-Allow-Origin: *");
header("HTTP/1.0 500 Error in geting Data");

include_once('fpdf.php');

$orientation = 'L'; //Page Orientation
$pageSize = 'A3';   //Page Size
$header = array();
$proc_name = "" ;
$proc_params=array() ;
$ph = "";

foreach($_GET as $key => $value)
{
    if(substr($key, 0, 3 ) == "pr_")
    {
        $proc_name = $value ;
    } 
    else if(substr($key, 0, 3 ) == "hd_")
    {
        array_push($header, $value);    
    }
    else if(substr($key, 0, 3 ) == "pa_")
    {
        array_push($proc_params, $value);    
    }
    else if(substr($key, 0, 3 ) == "ph")
    {
        $ph = $value;   
    }
}

    class PDF extends FPDF
    {
        // Page header
        function Header()
        {   
            //Page Headers
            global $header;
            // $pageHeaders = array('Header 1', 'Header 2', 'Header 3');
            $pdf = new PDF();
            $w = $pdf->GetPageWidth();
            $this->SetFont('Arial','',8);
            $this->Ln();
            $this->SetFont('Arial','B',13);
            // Title
            for($i=0; $i<sizeof($header); $i++){
                $this->Cell('',10,$header[$i].'',0,0,'C');
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

        //MultiCell Table
        var $widths;
        var $aligns;

        function SetWidths($w)
        {
            //Set the array of column widths
            $this->widths=$w;
        }

        function SetAligns($a)
        {
            //Set the array of column alignments
            $this->aligns=$a;
        }

        function Row($data, $borders, $drawRect)
        {
            //Calculate the height of the row
            $nb=0;
            for($i=0;$i<count($data);$i++)
                $nb=max($nb,$this->NbLines($this->widths[$i],$data[$i]));
            $h=5*$nb;
            //Issue a page break first if needed
            $this->CheckPageBreak($h);
            //Draw the cells of the row
            for($i=0;$i<count($data);$i++)
            {
                $w=$this->widths[$i];
                $a=isset($this->aligns[$i]) ? $this->aligns[$i] : "L";
                //Save the current position
                $x=$this->GetX();
                $y=$this->GetY();
                //Draw the border
                if($drawRect == 1)
                $this->Rect($x,$y,$w,$h);
                //Print the text
                $this->MultiCell($w,5,$data[$i], $borders,$a);
                //Put the position to the right of the cell
                $this->SetXY($x+$w,$y);
            }
            //Go to the next line
            $this->Ln($h);
        }

        function CheckPageBreak($h)
        {
            //If the height h would cause an overflow, add a new page immediately
            if($this->GetY()+$h>$this->PageBreakTrigger)
                $this->AddPage($this->CurOrientation);
        }

        function NbLines($w,$txt)
        {
            //Computes the number of lines a MultiCell of width w will take
            $cw=&$this->CurrentFont["cw"];
            if($w==0)
                $w=$this->w-$this->rMargin-$this->x;
            $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
            $s=str_replace("\r","",$txt);
            $nb=strlen($s);
            if($nb>0 and $s[$nb-1]=="\n")
                $nb--;
            $sep=-1;
            $i=0;
            $j=0;
            $l=0;
            $nl=1;
            while($i<$nb)
            {
                $c=$s[$i];
                if($c=="\n")
                {
                    $i++;
                    $sep=-1;
                    $j=$i;
                    $l=0;
                    $nl++;
                    continue;
                }
                if($c==" ")
                    $sep=$i;
                $l+=$cw[$c];
                if($l>$wmax)
                {
                    if($sep==-1)
                    {
                        if($i==$j)
                            $i++;
                    }
                    else
                        $i=$sep+1;
                    $sep=-1;
                    $j=$i;
                    $l=0;
                    $nl++;
                }
                else
                    $i++;
            }
            return $nl;
        }
    }

    require('config.php');
    global $proc_name;
    global $proc_params;
    global $ph;

    $connection = mysqli_connect($host, $user, $password, $dbname, $port);
    if (mysqli_connect_errno()) 
    {
        die("Connect failed : " . mysqli_connect_error());
    }
    $tableinfo = array();
    $sum = 0;
    $colInfo = explode(',', $ph);
    foreach ($colInfo as $value){
        $intArray [] = intval ($value);
    }
    $query  = "call $proc_name(" ;

    foreach ($proc_params as &$value) {
        $query = $query . "'" . mysqli_real_escape_string($connection, $value) . "'," ;
    }
    
    $query = substr($query, 0, -1) . ")" ;
   
    $result = mysqli_query($connection, $query);
    if(!$result)
    {
        die("Query failed");
    }

    $row = mysqli_fetch_fields($result);

    
    foreach($row as $val){
        $tableinfo[0][] = $val->name;
    
    }
    
    $pdf = new PDF($orientation, 'mm', $pageSize);
    
    //header
    $pdf->AddPage();
    $pdf->SetDrawColor(175, 177, 181);
    
    //foter page
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',12);
    $w = ($pdf->GetPageWidth()-28.35);
    
    for($i=0; $i<sizeof($tableinfo[0]); $i++){
        $tableinfo[1][] = ($w*$intArray[$i])/100;
        $tableinfo[2][] = "C";
    }
    $pdf->SetAligns($tableinfo[2]);
    $pdf->SetWidths($tableinfo[1]);
    $pdf->Row($tableinfo[0], "", 1);
    $pdf->SetFont('Arial','',12);
    
    // print_r($result);
    while($row = mysqli_fetch_row($result)){
        $pdf->Row($row, "", 1);
    }
    header("HTTP/1.0 200");
    $pdf->Output('', 'Test.pdf');
?>