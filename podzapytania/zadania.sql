-- zad 1
SELECT b.barcode_ean, i.description FROM barcode b JOIN item i ON i.item_id = b.item_id WHERE i.sell_price = ( SELECT MAX(sell_price) FROM item JOIN orderline USING (item_id) );


-- zad 2
SELECT orderinfo_id, date_placed FROM orderinfo WHERE customer_id = ( SELECT customer_id FROM customer WHERE fname = 'David' AND lname = 'Hudson' );


-- zad 3
SELECT lname, fname FROM customer WHERE customer_id IN ( SELECT customer_id FROM orderinfo WHERE date_placed BETWEEN '2000-07-01' AND '2000-07-30' );


-- zad 4
SELECT * FROM item WHERE sell_price > 3 AND item_id IN ( SELECT ol.item_id FROM orderline ol JOIN orderinfo o USING (orderinfo_id) WHERE MONTH(o.date_placed) = 9 );


-- zad 5
SELECT description, sell_price FROM item WHERE item_id IN ( SELECT ol.item_id FROM orderline ol JOIN orderinfo o USING (orderinfo_id) WHERE DAYOFWEEK(o.date_placed) = 2 );


-- zad 6
SELECT o.date_placed, DAYNAME(o.date_placed) AS dzien_tygodnia FROM orderinfo o JOIN orderline USING (orderinfo_id) WHERE item_id = ( SELECT item_id FROM item JOIN orderline USING (item_id) ORDER BY sell_price LIMIT 1 );


-- zad 7
SELECT barcode_ean FROM barcode WHERE item_id NOT IN ( SELECT item_id FROM orderline );


-- zad 8
SELECT c.lname, c.fname, c.title, o.date_placed FROM orderinfo o JOIN customer c USING (customer_id) WHERE c.customer_id IN ( SELECT customer_id FROM customer WHERE town LIKE '%town%' );


-- zad 9
SELECT lname, fname FROM customer WHERE customer_id IN ( SELECT o.customer_id FROM orderinfo o JOIN orderline ol USING (orderinfo_id) JOIN item i USING (item_id) GROUP BY o.customer_id, o.orderinfo_id HAVING SUM(ol.quantity * i.sell_price) > ( SELECT AVG(sell_price) FROM item ) );


-- zad 10
SELECT fname, lname FROM customer WHERE customer_id IN ( SELECT customer_id FROM orderinfo GROUP BY customer_id HAVING COUNT(*) > 1 );
