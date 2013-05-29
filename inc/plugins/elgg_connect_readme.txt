write a MyBB plugin to hook into user login, registration, post
call to Elgg API to post activity, wire
create a table to store username/ password in Mybb plugin then sync this user to Elgg
authenticate a user from MyBB to Elgg using the stored table
the password is randomly generated and the user actually doesnt know this password, he only can login to via MyBB before going to Elgg

basic flow:
user login to mybb
ElgConnect check if the user already exists in plugin db or not
if not: create a new elgg user with profile data from Mybb, the generated password is stored in plugin db
if yes: authenticate the user with Elgg, after that, if user clicks on Elgg site, the user is already authenticated