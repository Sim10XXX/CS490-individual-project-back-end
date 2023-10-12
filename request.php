<?php

    $q = $_REQUEST["q"];
    $v = $_REQUEST["v"];
    
    $cnx = new mysqli('localhost', 'root', 'Msf56288!)', 'sakila');
			
    if ($cnx->connect_error)
        die('Connection failed: ' . $cnx->connect_error);
    switch ($q){
        case 10:
            //count of available film to rent out
            $query = $cnx->prepare('  SELECT title, COUNT(*) AS rented
                        FROM rental, inventory, film
                        WHERE return_date IS NULL AND rental.inventory_id = inventory.inventory_id AND inventory.film_id = film.film_id 
                        AND film.title = ?
                        GROUP BY title
            ');
            $query->bind_param("s", $v);
            $query->execute();
            $cursor = $query->get_result();
            $row = $cursor->fetch_assoc();
            $rented = 0;
            if ($row != NULL){
                $rented = $row['rented'];
            }

            $query = $cnx->prepare('  SELECT title, COUNT(*) AS copies
                        FROM inventory, film
                        WHERE inventory.film_id = film.film_id AND film.title = ?
                        GROUP BY title
            ');
            $query->bind_param("s", $v);
            $query->execute();
            $cursor = $query->get_result();
            $row = $cursor->fetch_assoc();
            $copies = 0;
            if ($row != NULL){
                $copies = $row['copies'];
            }
            echo '<h2># Available in store: '. $copies - $rented .'</h2>';

            $query = $cnx->prepare('  SELECT inventory_id
                        FROM inventory, film
                        WHERE inventory.film_id = film.film_id AND inventory_id NOT IN (SELECT inventory.inventory_id
                                                    FROM rental, inventory, film
                                                    WHERE return_date IS NULL AND rental.inventory_id = inventory.inventory_id AND inventory.film_id = film.film_id 
                                                    AND film.title = ?)
                        AND film.title = ?
            ');
            $query->bind_param("ss", $v, $v);
            $query->execute();
            $cursor = $query->get_result();

            echo '<form action="update.php?q=3" method="post">
                Select Inventory ID:
                <select name="inventory_id" id="inventory_id">';
            while ($row = $cursor->fetch_assoc()) {
                echo '<option value="'. $row['inventory_id'] .'">'. $row['inventory_id'] .'</option>';
            }
            echo        '</select>
                <br>Customer ID to rent to: <input type="text" name= "customer_id" id= "customer_id" onkeyup="request(12, this.value)">
                <div id="customersubresponse"></div>
                <br>Staff ID: <input type="text" name= "staff_id" id= "staff_id" onkeyup="request(13, this.value)">
                <div id="staffsubresponse"></div>
                <br><br><input type="submit" class = "button3" value="Rent Movie">
                </form>
            ';
            break;
        case 12: //special case to show if customer id input is valid
            $query = $cnx->prepare('  SELECT customer_id
                        FROM customer
                        WHERE customer_id = ?
            ');
            $query->bind_param("s", $v);
            $query->execute();
            $cursor = $query->get_result();
            $row = $cursor->fetch_assoc();
            if ($row == NULL){
                echo '<p style="color:red;">Invalid Customer ID!</p>';
            }
            break;
        case 13: //special case to show if staff id input is valid
            $query = $cnx->prepare('  SELECT staff_id
                        FROM staff
                        WHERE staff_id = ?
            ');
            $query->bind_param("s", $v);
            $query->execute();
            $cursor = $query->get_result();
            $row = $cursor->fetch_assoc();
            if ($row == NULL){
                echo '<p style="color:red;">Invalid Staff ID!</p>';
            }
            break;
        //Home page requests
        //
        //
        case 2: //Details of movie
            $query = $cnx->prepare('  SELECT title, release_year, description, length, rating, rental_rate
                        FROM film
                        WHERE title = ?
            ');
			$query->bind_param("s", $v);
            $query->execute();
            $cursor = $query->get_result();
            $row = $cursor->fetch_assoc();
            
            echo '<h3> '. $row['title'] .' ('. $row['release_year'] .') '. $row['length'] .' min, rated: '. $row['rating'] .'</h3><p>'. $row['description'] .'</p>';
            
            break;
        case 1: //Top 5 movies
            $query = $cnx->prepare('  SELECT title, COUNT(*) AS rented
                        FROM rental, inventory, film
                        WHERE rental.inventory_id = inventory.inventory_id AND inventory.film_id = film.film_id
                        GROUP BY title
                        ORDER BY COUNT(*) DESC
                        LIMIT 5
            ');
			$query->execute();
            $cursor = $query->get_result();

            echo '<table><h3>';
            echo "<tr><th>Movies</th><th># of rentals</th></tr>";
			while ($row = $cursor->fetch_assoc()) {
				echo '<tr><td> <button type="button" class="button2" onclick="subrequest(2, \''. $row['title'] .'\')">' . $row['title'] . ' </button></td><td> ' . $row['rented'] . ' </td></tr>';
            }
            echo '</h3></table>';
            break;
        
        case 4: //Actor details
            $query = $cnx->prepare('  SELECT title, first_name, last_name, COUNT(*) AS rented
                        FROM rental, inventory, film, film_actor, actor
                        WHERE rental.inventory_id = inventory.inventory_id AND inventory.film_id = film.film_id AND film_actor.film_id = film.film_id AND film_actor.actor_id = ? AND film_actor.actor_id = actor.actor_id
                        GROUP BY title, first_name, last_name
                        ORDER BY COUNT(*) DESC
                        LIMIT 5
            ');
			$query->bind_param("s", $v);
            $query->execute();
            $cursor = $query->get_result();
            $row = $cursor->fetch_assoc();
            echo '<h3>Top 5 Movies for '. $row['first_name'] . ' ' . $row['last_name'] .'</h3>';
            echo '<table><h3>';
            echo "<tr><th>Movies</th><th># of rentals</th></tr>";
            do{
                echo '<tr><td> <button type="button" class="button2" onclick="subrequest(2, \''. $row['title'] .'\')">' . $row['title'] . ' </button></td><td> ' . $row['rented'] . ' </td></tr>';
            }while($row = $cursor->fetch_assoc());
            echo '</h3></table>';
            break;
        case 3: //Top 5 actors
            $query = $cnx->prepare('  SELECT actor.actor_id, first_name, last_name, COUNT(*) AS movies
                        FROM film, film_actor, actor
                        WHERE film.film_id = film_actor.film_id AND film_actor.actor_id = actor.actor_id
                        GROUP BY actor_id
                        ORDER BY COUNT(*) DESC
                        LIMIT 5
            ');
			$query->execute();
            $cursor = $query->get_result();
            echo '<table><h3>';
            echo "<tr><th>Actor</th><th># of movies</th></tr>";
			while ($row = $cursor->fetch_assoc()) {
				echo '<tr><td><button type="button" class="button2" onclick="subrequest(4, \''. $row['actor_id'] .'\')"> ' . $row['first_name'] . ' ' . $row['last_name'] . ' </button></td><td> ' . $row['movies'] . ' </td></tr>';
            }
            echo '</h3></table>';
            break;

        //Report page requests
        //
        //
        case 5: //download pdf

            header("Location: reportpdf.php"); /* Redirect browser */

            /* Make sure that code below does not get executed when we redirect. */
            exit;

            break;

        //Movie page requests
        //
        //
        case 6: //Search movie list by title or actor
            $g = $_REQUEST["g"];
            if ($g == 'Any'){
                $query = $cnx->prepare('  SELECT title
                            FROM film, film_actor, actor
                            WHERE film.film_id = film_actor.film_id AND film_actor.actor_id = actor.actor_id AND 
                            (actor.first_name LIKE CONCAT( "%",?,"%") OR title LIKE CONCAT( "%",?,"%"))
                            GROUP BY title
                ');
                $query->bind_param("ss", $v, $v);
            }
            else{
                $query = $cnx->prepare('  SELECT title
                            FROM film, film_actor, actor, film_category, category
                            WHERE film.film_id = film_actor.film_id AND film_actor.actor_id = actor.actor_id AND 
                            (actor.first_name LIKE CONCAT( "%",?,"%") OR title LIKE CONCAT( "%",?,"%")) AND 
                            film.film_id = film_category.film_id AND film_category.category_id = category.category_id
                            AND category.name = ?
                            GROUP BY title
                ');
                $query->bind_param("sss", $v, $v, $g);
            }
            
            //$cursor = $cnx->query($query);
            $query->execute();
            $cursor = $query->get_result();
            if (mysqli_num_rows($cursor) == 0){
                echo '<p>No Movies :(</p>';
            }
            else{
                echo '<table><h3>';
                echo "<tr><th>Movie Title</th></tr>";
                while($row = $cursor->fetch_assoc()){
                    echo '<tr><td> <button type="button" class="button2" onclick="subrequest(10, \''. $row['title'] .'\')">' . $row['title'] . ' </button></td></tr>';
                }
                echo '</h3></table>';
            }
            
            break;

        //Customer page requests
        //
        //
        case 7: //List of customers depending on filtered on user input
            $x = $_REQUEST["x"];
            $y = $_REQUEST["y"];
            $z = $_REQUEST["z"];

            $query = $cnx->prepare('  SELECT customer_id, first_name, last_name
                        FROM customer
                        WHERE customer_id LIKE CONCAT( "%",?,"%") AND first_name LIKE CONCAT( "%",?,"%") AND last_name LIKE CONCAT( "%",?,"%")
            ');
            $query->bind_param("sss", $x, $y, $z);
            $query->execute();
            $cursor = $query->get_result();
            echo '<table><h3>';
            echo "<tr><th>Customer ID</th><th>First Name</th><th>Last Name</th></tr>";
            while($row = $cursor->fetch_assoc()){
                echo '<tr><td> <button type="button" class="button2" onclick="subrequest(8, \''. $row['customer_id'] .'\')">' . $row['customer_id'] . ' </button></td>
                <td>' . $row['first_name'] . '</td>
                <td>' . $row['last_name'] . '</td></tr>';
            }
            echo '</h3></table>';
            break;
        case 8: //Customer details and edit capability
            $query = $cnx->prepare('  SELECT customer_id, first_name, last_name, email, active, create_date, country, city, address, address2, district, postal_code, phone
                        FROM customer, address, city, country
                        WHERE customer.address_id = address.address_id AND address.city_id = city.city_id AND city.country_id = country.country_id
                        AND customer_id = ?
            ');
            $query->bind_param("s", $v);
            $query->execute();
            $cursor = $query->get_result();
            $row = $cursor->fetch_assoc();
            echo '<form action="update.php?q=1&id='. $row['customer_id'] .'" method="post">
            <p>Customer ID: '. $row['customer_id'] .'<br>Create Date: "'. $row['create_date'] .'"</p>
            First Name: <input type="text" name = "first_name" id= "first_name" value = "' . $row['first_name'] . '"><br>
             Last Name: <input type="text" name = "last_name" id= "last_name" value = "' . $row['last_name'] . '"><br>
             Email: <input type="text" name = "email" id= "email" value = "' . $row['email'] . '"><br>
             Active: <input type="text" name = "active" id= "active" value = "' . $row['active'] . '"><br>
             Country: <input type="text" name = "country" id= "country" value = "' . $row['country'] . '">
             City: <input type="text" name = "city" id= "city" value = "' . $row['city'] . '"><br>
             Address: <input type="text" name = "address" id= "address" value = "' . $row['address'] . '">
             Address 2: <input type="text" name = "address2" id= "address2" value = "' . $row['address2'] . '"><br>
             District: <input type="text" name = "district" id= "district" value = "' . $row['district'] . '">
             Postal Code: <input type="text" name = "postal_code" id= "postal_code" value = "' . $row['postal_code'] . '"><br>
             Phone: <input type="text" name = "phone" id= "phone" value = "' . $row['phone'] . '"><br>
             <br><input type="submit" class="button3" value="Update Values">
             <br><br><br><br><br>
        </form>
        
        <form action="update.php?q=2&id='. $row['customer_id'] .'" method="post">
            <input type="submit" value="Delete Customer" class="button4" onclick="return confirm(\'This will delete the customer and all rental records associated with them.\nAre you sure?\')">
        </form>
        ';
            
            
            $query = $cnx->prepare('  SELECT title, rental_date, return_date
                        FROM rental, customer, inventory, film
                        WHERE inventory.inventory_id = rental.inventory_id AND inventory.film_id = film.film_id
                        AND rental.customer_id = customer.customer_id AND customer.customer_id = ?
            ');
            $query->bind_param("s", $v);
            $query->execute();
            $cursor = $query->get_result();
            echo'<h3>Rented Movies</h3>';
            echo '<table><h3>';
            echo "<tr><th>Movie Tile</th><th>Rental Date</th><th>Return Date</th></tr>";
            while($row = $cursor->fetch_assoc()){
                echo '<tr><td>' . $row['title'] . '</td>
                <td>' . $row['rental_date'] . '</td>
                <td>' . $row['return_date'] . '</td></tr>';
            }
            echo '</h3></table>';
            break;
        case 9: //Add new customer
            echo '<form action="update.php?q=0" method="post">
            <div class="required-field">First Name: <input type="text" id= "first_name" name= "first_name" required></div>
            <div class="required-field">Last Name: <input type="text" id= "last_name" name= "last_name" required></div>
             Email: <input type="text" id= "email" name= "email" ><br>
             Active: <input type="text" id= "active" name= "active" ><br>
             <div class="required-field">Country: <input type="text" id= "country" name= "country" required>
             <div class="required-field">City: <input type="text" id= "city" name= "city" required><br></div>
             <div class="required-field">Address: <input type="text" id= "address" name= "address" required>
             Address 2: <input type="text" id= "address2" name= "address2" ><br>
             <div class="required-field">District: <input type="text" id= "district" name= "district" required>
             Postal Code: <input type="text" id= "postal_code" name= "postal_code" ><br>
             <div class="required-field">Phone: <input type="text" id= "phone" name= "phone" required><br>

             <br><input type="submit" class = "button3" value="Create New Customer">
        </form>';
            break;
        /*case -1: //test case
            $query = '  SELECT country
                        FROM country
                        WHERE country = "Algeri"
            ';
            $cursor = $cnx->query($query);
            $row = $cursor->fetch_assoc();
            if ($row != NULL){
                echo "not null";
            }
            else{
                echo "null";
            }
            break;*/
    }

    //echo $v;
?> 