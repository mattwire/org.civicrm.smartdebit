## Manual Sync
!!! tip "Usage"
    This is normally done automatically via the scheduled synchronisation job.
    Manual sync would normally be used for troubleshooting or initial setup of Smartdebit with CiviCRM.

!!! tip "Cached collection reports"
    Manual sync can be used to synchronise any collection reports that are downloaded, synchronised, or it can download the latest daily reports. 
    This is very useful if reinstalling or syncing with Smartdebit for the first time.

You can perform a manual sync by selecting _Manual Sync_ from the __Administer->CiviContribute->Smart Debit__ menu.

## Step 1: Start Manual Sync
![manual sync step 1](/images/smartdebit_manualsync1.png)

!!! tip "Clearing and populating the collection reports cache"
    * To clear the existing cache use the API function: [Smartdebit.clearcache](/api/#smartdebitclearcache)
    * To download specific collection reports use the API function: [Smartdebit.retrievecollectionreports](/api/#smartdebitretrievecollectionreports)

## Step 2: Select AUDDIS/ARUDD dates
![Manual sync step 2](/images/smartdebit_manualsync2.png)

## Step 3: View data
![Manual sync step 3](/images/smartdebit_manualsync3.png)

## Step 4: Confirm Sync
If you have anything to sync you can begin the sync
