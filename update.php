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

    $query = $cnx->prepare('  SELECT country_id
            FROM country
            WHERE country = ?
    ');
    $query->bind_param("s", $_POST["country"]);
    $query->execute();
    $cursor = $query->get_result();
    $row = $cursor->fetch_assoc();
    if ($row == NULL){
        $query = $cnx->prepare('INSERT INTO country (country)
                    VALUES (?)
        ');
        $query->bind_param("s", $_POST["country"]);
        $query->execute();
        $cursor = $query->get_result();
        $last_id = $cnx->insert_id;
    }
    else{
        $last_id = $row["country_id"];
    }

    //Check city

    $query = $cnx->prepare('  SELECT city_id
                FROM city
                WHERE city = ?
    ');
    $query->bind_param("s", $_POST["city"]);
    $query->execute();
    $cursor = $query->get_result();
    $row = $cursor->fetch_assoc();

    if ($row == NULL){
        $query = $cnx->prepare('  INSERT INTO city (city, country_id)
                    VALUES (?, ?)
        ');
        $query->bind_param("ss", $_POST["city"], $last_id);
        $query->execute();
        $cursor = $query->get_result();
        $last_id = $cnx->insert_id;
    }
    else{
        $last_id = $row["city_id"];
    }

    //Check Address

    $query = $cnx->prepare('  SELECT address_id
                FROM address
                WHERE address = ? AND district = ? AND phone = ?
    ');
    $query->bind_param("sss", $_POST["address"], $_POST["district"], $_POST["phone"]);
    $query->execute();
    $cursor = $query->get_result();
    $row = $cursor->fetch_assoc();

    if ($row == NULL){
        // !!! location is currently always (0, 0) !!!
        $query = $cnx->prepare('  INSERT INTO address (address, address2, district, city_id, postal_code, phone, location)
                    VALUES (?, ?, ?, ?, ?, ?, POINT(0,0))
        ');
        $query->bind_param("ssssss", $_POST["address"], $_POST["address2"], $_POST["district"], $last_id, $_POST["postal_code"], $_POST["phone"]);
        $query->execute();
        $cursor = $query->get_result();
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
        $query = $cnx->prepare('  INSERT INTO customer (first_name, last_name, email, store_id, address_id)
                    VALUES (?, ?, ?, 1, ?)
        ');
        $query->bind_param("ssss", $_POST["first_name"], $_POST["last_name"], $_POST["email"], $last_id);
        $query->execute();
        $cursor = $query->get_result();
        
        header("Location: customers.html"); /* Redirect browser */
        exit;

        break;
    case 1: //q==1: update customer values
        $id = $_REQUEST["id"]; 

        $last_id = updatelocations($cnx);

        $query = $cnx->prepare('  UPDATE customer
                    SET first_name = ?, last_name = ?, email = ?,
                     active = ?, address_id = ?
                    WHERE customer_id = ? 
        ');
        $query->bind_param("ssssss", $_POST["first_name"], $_POST["last_name"], $_POST["email"], $_POST["active"], $last_id, $id);
        $query->execute();
        $cursor = $query->get_result();

        

        header("Location: customers.html"); /* Redirect browser */
        exit;

        break;
    case 2: //q==2: delete customer
        $id = $_REQUEST["id"]; 

        $query = $cnx->prepare('  DELETE FROM rental
                    WHERE customer_id = ?;
        ');
        $query->bind_param("s", $id);
        $query->execute();
        $cursor = $query->get_result();

        $query = $cnx->prepare('  DELETE FROM customer 
                    WHERE customer_id = ?;
        ');
        $query->bind_param("s", $id);
        $query->execute();
        $cursor = $query->get_result();

        header("Location: customers.html"); /* Redirect browser */
        exit;


        break;
    case 3: //q==3: rent a movie
        $query = $cnx->prepare('  SELECT customer_id
                    FROM customer
                    WHERE customer_id = ?
        ');
        $query->bind_param("s", $v);
        $query->execute();
        $cursor = $query->get_result();
        $row = $cursor->fetch_assoc();
        if ($row == NULL){
            header("Location: movies.html"); /* Redirect browser */
            exit;
        }

        $query = $cnx->prepare('  SELECT staff_id
                    FROM staff
                    WHERE staff_id = ?
        ');
        $query->bind_param("s", $v);
        $query->execute();
        $cursor = $query->get_result();
        $row = $cursor->fetch_assoc();
        if ($row == NULL){
            header("Location: movies.html"); /* Redirect browser */
            exit;
        }


        $query = $cnx->prepare('  INSERT INTO rental (inventory_id, customer_id, staff_id, rental_date)
                    VALUES (?, ?, ?, now())
        ');
        $query->bind_param("sss", $_POST["inventory_id"], $_POST["customer_id"], $_POST["staff_id"]);
        $query->execute();
        $cursor = $query->get_result();

        header("Location: movies.html"); /* Redirect browser */
        exit;
        
        break;
}  
?>