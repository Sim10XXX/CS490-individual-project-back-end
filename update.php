<?php
$q = $_REQUEST["q"]; 
//q==0: create new customer
//q==1: update customer values
//q==2: delete customer
//q==3: rent a movie
echo "yep";
//echo $_POST["email"];
$cnx = new mysqli('localhost', 'root', 'Msf56288!)', 'sakila');
			
if ($cnx->connect_error)
    die('Connection failed: ' . $cnx->connect_error);


function updatelocations($cnx){
    
    //Check country

    $query = '  SELECT country_id
            FROM country
            WHERE country = "'.$_POST["country"].'"
    ';
    $cursor = $cnx->query($query);
    $row = $cursor->fetch_assoc();
    if ($row == NULL){
        $query = 'INSERT INTO country (country)
                    VALUES ("'.$_POST["country"].'")
        ';
        $cursor = $cnx->query($query);
        $last_id = $cnx->insert_id;
    }
    else{
        $last_id = $row["country_id"];
    }

    //Check city

    $query = '  SELECT city_id
                FROM city
                WHERE city = "'.$_POST["city"].'"
    ';
    $cursor = $cnx->query($query);
    $row = $cursor->fetch_assoc();

    if ($row == NULL){
        $query = '  INSERT INTO city (city, country_id)
                    VALUES ("'.$_POST["city"].'", "'.$last_id.'")
        ';
        $cursor = $cnx->query($query);
        $last_id = $cnx->insert_id;
    }
    else{
        $last_id = $row["city_id"];
    }

    //Check Address

    $query = '  SELECT address_id
                FROM address
                WHERE address = "'.$_POST["address"].'" AND district = "'.$_POST["district"].'" AND phone = "'.$_POST["phone"].'"
    ';
    $cursor = $cnx->query($query);
    $row = $cursor->fetch_assoc();

    if ($row == NULL){
        // !!! location is currently always (0, 0) !!!
        $query = '  INSERT INTO address (address, address2, district, city_id, postal_code, phone, location)
                    VALUES ("'.$_POST["address"].'", "'.$_POST["address2"].'", "'.$_POST["district"].'", "'.$last_id.'", "'.$_POST["postal_code"].'", "'.$_POST["phone"].'", POINT(0,0))
        ';
        $cursor = $cnx->query($query);
        $last_id = $cnx->insert_id;
    }
    else{
        $last_id = $row["address_id"];
    }
    return $last_id;
}


switch ($q){
    case 0: //q==0: create new customer
        $last_id = updatelocations($cnx);

        //Insert Customer

        // !!! store id is currently always 1 !!!
        $query = '  INSERT INTO customer (first_name, last_name, email, store_id, address_id)
                    VALUES ("'.$_POST["first_name"].'", "'.$_POST["last_name"].'", "'.$_POST["email"].'", 1, "'.$last_id.'")
        ';
        $cursor = $cnx->query($query);
        
        header("Location: customers.html"); /* Redirect browser */
        exit;

        break;
    case 1: //q==1: update customer values
        $id = $_REQUEST["id"]; 

        $last_id = updatelocations($cnx);

        $query = '  UPDATE customer
                    SET first_name = "'.$_POST["first_name"].'", last_name = "'.$_POST["last_name"].'", email = "'.$_POST["email"].'",
                     active = "'.$_POST["active"].'", address_id = "'.$last_id.'"
                    WHERE customer_id = '.$id.' 
        ';
        $cursor = $cnx->query($query);

        

        header("Location: customers.html"); /* Redirect browser */
        exit;

        break;
    case 2: //q==2: delete customer
        // !!! this will only work with test customer, because dependecies aren't taken care of !!!
        $id = $_REQUEST["id"]; 
        $query = '  DELETE FROM customer 
                    WHERE customer_id = '.$id.';
        ';
        $cursor = $cnx->query($query);

        header("Location: customers.html"); /* Redirect browser */
        exit;


        break;
    case 3: //q==3: rent a movie
        $query = '  INSERT INTO rental (inventory_id, customer_id, staff_id, rental_date)
                    VALUES ("'.$_POST["inventory_id"].'", "'.$_POST["customer_id"].'", "'.$_POST["staff_id"].'", now())
        ';
        $cursor = $cnx->query($query);

        header("Location: movies.html"); /* Redirect browser */
        exit;
        
        break;
}  
?>