MyBB GoMobile Readme

-- This product is licensed under the GNU/GPL v3 license, meaning you are free to do anything within the scope of the license. Within this package, you should find a copy of the license titled 'License.txt'.

-- This is designed for MyBB 1.6. As with any beta plugin, please backup your database before proceeding. If you want to install this on 1.4, proceed with caution - though this is not supported. We are not responsible for any damage caused to your forums/website due to misconfiguration/misuse on either 1.4 or 1.6.


INSTALLATION
------------
The GoMobile installation procedure should be followed as it is listed below.


1. Upload the contents of the "Upload" folder to the root of your forums, keep the folder structure intact.

2. Visit your ACP > Configuration > Plugins and hit 'Install & Activate' for MyBB GoMobile.


CHECKS
------
Before assuming your install was 100% successful, check the following.

1. While still in your AdminCP, there will be a new option under Configuration on the left sidebar called MyBB GoMobile. Check that this exists.

2. Check your database. There should be an additional table called prefix_gomobile (prefix will vary), containing 3 fields. Now check the threads and posts tables. They should each have an extra column at the end titled 'mobile'. If the database has been changed to the listed items, awesome.

3. Check one of your templatesets. Under the postbit category, look in postbit_posturl. This should have some extra code (an image) in it.

4. Of course, check that GoMobile actually works when you visit on a mobile device.


If the above checks pass, congratulations! GoMobile should be installed and configured correctly.