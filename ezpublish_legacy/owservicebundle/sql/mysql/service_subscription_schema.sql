CREATE TABLE service_subscription ( 
id int(11) NOT NULL AUTO_INCREMENT, 
user_id int(11) NOT NULL, 
service_link_id int(11) NOT NULL, 
PRIMARY KEY ( id ), 
KEY user_id ( user_id ), 
KEY service_link_id ( service_link_id ) 
)

