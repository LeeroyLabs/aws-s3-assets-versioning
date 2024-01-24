# AWS S3 Assets Versioning for Craft CMS 4.x

Plugin to add versioning to assets with S3 in Craft CMS

## Requirements

This plugin requires Craft CMS 4.0.0 or later and an AWS S3 bucket with versioning enabled.

## Installation

To install the plugin, follow these instructions.
> **You need to have versioning enable on your S3 Bucket, here's how:** https://docs.aws.amazon.com/AmazonS3/latest/userguide/manage-versioning-examples.html

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require leeroy/aws-s3-assets-versioning

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for AWS S3 Assets Versioning.

## Assets Versioning Overview

When you create a draft, a button to generate a share link will appear to the right below the changes note box.

![](resources/img/draft-sharer-button-preview.png)

Brought to you by [Leeroy agency](https://github.com/LeeroyLabs/)