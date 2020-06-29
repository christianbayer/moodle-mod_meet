# Meet Activity Module for Moodle

[Google Meet](https://meet.google.com/) is a real-time meeting service service developed by Google. This module integrates its funcionalities with Moodle.

With the latest version of this plugin, you can:
 - Create Meet activities from any course, that will create an event in the owner's calendar and invite everyone in the course, attatching a Meet call to it;
 - Specify join open/close times that will appear in Moodle calendar
 - Launch Meet in its own tab;
 - Record and playback those recordings from Moodle (only available if the recording is saved in Google Drive);
 - View reports of the meetings with its participants, join time, left time, call duration and attendance list (requires a G Suite account with reporting capabilities);


## Prerequisites
- A Moodle wesite with HTTPS enabled;
- A G Suite account to manage events (optionally with reporting capabilities);
- A [G Suite Admin](https://admin.google.com/) account to set permissions;
- A [Google Cloud Platform](https://console.cloud.google.com/) account;
- A [Google Cloud Platform](https://console.cloud.google.com/) project;


## Set-up

### Creating a service account

Open your [Google Cloud Platform](https://console.cloud.google.com/) project and follow the steps:

1. In the [Library](https://console.cloud.google.com/apis/library), enable the following scopes: 
```
Google Calendar API
Google Drive API
Admin SDK
```
2. Configure the [OAuth consent screen](https://console.cloud.google.com/apis/credentials/consent) with user type as **Internal**;
3. Add your domain to the [Domain verification](https://console.cloud.google.com/apis/credentials/domainverification) list (you may need to configure it in the [Search Console](https://www.google.com/webmasters/tools) first);
4. In the [Credentials](https://console.cloud.google.com/apis/credentials) area, create a **Service Account**, setting up the name and the role of **Project Manager**;
5. Go to [Service Accounts](https://console.cloud.google.com/iam-admin/serviceaccounts) and edit the created account. Toggle the **Show domain-wide delegation** and check that checkbox. Don't forget to **save** it;
6. Go back to **Service Accounts** and create a key with **JSON format**. Warning: keep this file safe and sound; you cannot get it back;
7. Copy the Client ID for that Service Account.


### Delegating domain-wide authority

Go to your G Suite domain's [Admin Console](http://admin.google.com/) and follow the steps:
1. Select **Security**, then **Advanced settings** and **Manage API client access**; 
2. In **Client name** field, enter the Client ID you've copied earlier;
3. In **One or More API Scopes**, enter the following scopes: 
```
https://www.googleapis.com/auth/calendar
https://www.googleapis.com/auth/calendar.events
https://www.googleapis.com/auth/drive
https://www.googleapis.com/auth/drive.appdata
https://www.googleapis.com/auth/drive.file
https://www.googleapis.com/auth/drive.metadata
https://www.googleapis.com/auth/admin.reports.audit.readonly **(optional)**
```
4. Finally, click the **Authorize** button.


### Creating a calendar
Go to [Google Calendar](https://calendar.google.com/calendar) and create a new calendar. Edit it's configuration and copy the **Calendar ID**.


### Configuring the Meet Module
After downloading and installing the module, we neet to configure it.

1. In **Credentials file**, upload the Service Account JSON credentials file generated earlier;
2. In **Calendar Owner E-mail**, set the email of the owner of the calendar created earlier;
3. In **Calendar ID**, set the calendar ID that you've copied earlier;
4. In **Enable reports** set whether the reports will be available or not (requires a G Suite account with reporting capabilities);
5. Dont forget to **save** the configuration.

