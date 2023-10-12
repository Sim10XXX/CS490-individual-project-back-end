<?php
require ('fpdf/fpdf.php');

$cnx = new mysqli('localhost', 'root', 'Msf56288!)', 'sakila');
        
if ($cnx->connect_error)
    die('Connection failed: ' . $cnx->connect_error);
$query = $cnx->prepare('  SELECT first_name, last_name, title
                FROM rental, customer, inventory, film
                WHERE rental.customer_id = customer.customer_id AND inventory.inventory_id = rental.inventory_id 
                AND film.film_id = inventory.film_id
');
$query->execute();
$cursor = $query->get_result();


for ($i = 0; $row = $cursor->fetch_assoc(); $i++){
    $tabledata[$i] = array($row['first_name'].' '.$row['last_name'], $row['title']);
}
$head = array("Customer Name", "Rented Movie Title");

class PDF extends FPDF{
    function BasicTable($header, $data){
        // Header
        $this->SetFont('Arial','B',12);
        foreach($header as $col)
            $this->Cell(80,7,$col,1);
        $this->Ln();
        // Data
        $this->SetFont('Arial','',10);
        foreach($data as $row)
        {
            foreach($row as $col)
                $this->Cell(80,6,$col,1);
            $this->Ln();
        }
    }
}

$pdf = new PDF();
$pdf->AddPage();

$pdf->BasicTable($head,$tabledata);

$pdf->Output('report.pdf', 'D');

?>