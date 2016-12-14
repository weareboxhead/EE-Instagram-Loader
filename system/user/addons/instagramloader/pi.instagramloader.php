<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

class InstagramLoader {
	private $config = array(
		'user_id' 		=> '',
		'client_id' 	=> '',
		'access_token' 	=> '',
		'channel_id' 	=> '',

		'fieldIds' 		=> array(
			'image_url' 	=>	'',
			'caption' 		=>	'',
			'id' 			=>	'',
			'link' 			=>	'',
			'width' 		=>	'',
			'height' 		=>	'',
			'orientation' 	=>	'',
		),
	);

	public $group_id;
	public $member_id;

	public function __construct()
	{
		if (!$this->configValid()) {
			return false;
		}

		// If we have new content returned from the API call...
		if ($content = $this->returnInstagrams())
		{
			$this->enableWritePermissions();
			$this->writeInstagrams($content);
			$this->resetPermissions();
		}
	}

	function enableWritePermissions() {
		// Get current user data
		$user_data = ee()->session->userdata;

		// Store permissions
		$this->group_id = $user_data['group_id'];
		$this->member_id = $user_data['member_id'];

		// Set to temporary super admin
		ee()->session->userdata['group_id'] = 1;
		ee()->session->userdata['member_id'] = 1;
	}

	function resetPermissions() {
		// Reset permissions
		ee()->session->userdata['group_id'] = $this->group_id;
		ee()->session->userdata['member_id'] = $this->member_id;
	}

	function isEmpty($item) {
		return empty($item);
	}

	function configValid() {
		// Check everything we need is set
		foreach ($this->config as $item) {
			// If this is an array
			if (is_array($item)) {
				// Check each sub item
				foreach ($item as $subItem) {
					if ($this->isEmpty($subItem)) {
						return false;
					}
				}
			// Otherwise
			} else {
				// Check the item
				if ($this->isEmpty($item)) {
					return false;
				}
			}
		}

		return true;
	}

	function getConfig($item) {
		if (is_array($item)) {
			return $this->config[$item[0]][$item[1]];
		}

		return $this->config[$item];
	}

	function returnInstagrams() {

		// Instantiate the Instagram wrapper object
		require_once dirname(__FILE__) . '/instagram/Instagram.php';
		if (!$instagram = new \MetzWeb\Instagram\Instagram($this->getConfig('client_id'))) return false;

		$instagram->setAccessToken($this->getConfig('access_token'));

		$localIds = array();

		// Get most recent instagram in EE db's id number
		$query = 'SELECT field_id_' . $this->getConfig(['fieldIds', 'id']) . ' FROM exp_channel_titles AS ct JOIN exp_channel_data AS cd ON ct.entry_id = cd.entry_id WHERE ct.channel_id = ' . $this->getConfig('channel_id') . ' ORDER BY ct.entry_date DESC LIMIT 20';

		$result = ee()->db->query($query);

		// If the query succeeded, add the ids to our array 
		if ($result->num_rows > 0) {
			foreach ($result->result() as $row) {
				$localIds[] = $row->{'field_id_' . $this->getConfig(['fieldIds', 'id'])};
			}
		}

		// Call for any missing instagrams
		if (!$result = $instagram->getUserMedia($this->getConfig('user_id'))) return false;

		// We just need the data array
		$content = $result->data;

		// For each instagram
		foreach ($content as $key => $instagram) {
			// If its id exists in our local ids array
			if (in_array($instagram->id, $localIds)) {
				// We already have it so disregard
				unset($content[$key]);
			}
		}

		return $content;
	}

	function writeInstagrams($content) {
		// Instantiate EE Channel Entries API
		ee()->load->library('api');
		ee()->legacy_api->instantiate('channel_entries');


		foreach($content as $instagram)
		{
			// If either the title, image url, entry date, id or link are missing, move on to the next item
			if (!isset($instagram->caption->text) || !isset($instagram->images->standard_resolution->url) || !isset($instagram->created_time) || !isset($instagram->id) || !isset($instagram->link)) {
				continue;
			}

			// Set variables from instagram object
			$image 			= $instagram->images->standard_resolution;
			$image_url 		= $image->url;
			$caption 		= $instagram->caption->text;
			$id 			= $instagram->id;
			$entry_date 	= $instagram->created_time;
			$link 			= $instagram->link;
			$width 			= $image->width;
			$height 		= $image->height;
			$orientation 	= '';

			// If this is non-square, set the orientation field
			if ($width !== $height) {
				if ($width > $height) {
					$orientation = 'landscape';
				} else {
					$orientation = 'portrait';
				}
			}

			// Escape any quotes in the caption
			$caption = htmlspecialchars($caption, ENT_QUOTES);

			$data = array(
				'title'														=>	substr($caption, 0, 30) . '...',
				'entry_date'												=>	$entry_date,
				'field_id_' . $this->getConfig(['fieldIds', 'image_url'])	=> 	$image_url,
				'field_id_' . $this->getConfig(['fieldIds', 'caption'])		=> 	$caption,
				'field_id_' . $this->getConfig(['fieldIds', 'id'])			=>	$id,
				'field_id_' . $this->getConfig(['fieldIds', 'link'])		=>	$link,
				'field_id_' . $this->getConfig(['fieldIds', 'width'])		=>	$width,
				'field_id_' . $this->getConfig(['fieldIds', 'height'])		=>	$height,
				'field_id_' . $this->getConfig(['fieldIds', 'orientation'])	=>	$orientation,
			);

			// Write the entry to the database
			ee()->api_channel_entries->save_entry($data, intval($this->getConfig('channel_id')));
		}
	}

}

