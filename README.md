# Instagram Loader (EE 3)

Retrieves Instagram content from a given account and saves the content as individual Channel Entries.

More specifically, Instagram Loader retrieves the latest Instagrams for the given account, and checks which of them are missing from the DB and creates and entry for each if it is missing.

## Usage

* Download and extract the plugin files
* Copy `/system/expressionengine/third_party/instagram-loader` to your site's `/system/expressionengine/third_party/` directory
* Create the [Fields](#fields) and a corresponding Channel
* Fill in the [config](#config)
* Install the plugin
* Include `{exp:instagramloader}` in a template file and load the template

## <a name="fields"></a>Fields

Instagram Loader uses the following fields, which should all be of type 'Text Input' and can be named as you wish:

* Image Url
* Caption
* Id (of the Instagram post, not the EE entry)
* Link
* Width
* Height
* Orientation

## <a name="config"></a>Config

The config array is located in `/system/expressionengine/third_party/instagram-loader/instagram-loader.php`.

Each key needs a value (all are required) and should be a string.

### User Id

This is the id of the Instagram account for which you are wanting to retrieve content.

### Client Id

This is the id of the Client created in [https://instagram.com/developer/](https://www.instagram.com/developer/).

### Access Token

For instructions on obtaining a valid access token, see [https://instagram.com/developer/authentication](https://www.instagram.com/developer/authentication/).

The required scope for this plugin is "public_content".

### Channel Id

This is the id of your "Instagram" channel.

### Field Ids

The id corresponding to the field.

## Instagram Restrictions

Due to recent restrictions imposed by Instagram:

* The API calls are limited to only the most recent 20 posts
* The user id must be either the owner of the account in which the Instagram Client (from which the client_id and access_token is generated), or be an authorized member of that account's Sandbox