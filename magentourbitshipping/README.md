#Urb-It - Magento configuration#

##Prerequisites##

1. You have an account with urb-it and have received the integration details for your urbit-account.

* Pickup location id
* Consumer secret
* Consumer secret key   
* oAuth token

2. Module is installed on your Magento installation.

##Step-by-step configuration##

1. Log in to the Magento administration panel.
2. Proceed to ”System” > ”Configuration” > ”Sales” > ”Shipping Methods”.
3. Enter your settings in the fields of ’Urbit - One hour’.

| Field                                      | Explanation                                                                          |
|--------------------------------------------|--------------------------------------------------------------------------------------|
| Enabled                                    | Set this to ’Yes’ to be able to use the shipping method.                             |
| Title                                      | Name of the shipping option in the checkout                                          |
| Environment                                | Live, staging or test environment at urb-it. Should be live on customer sites.       |
| Pickup location - Id                       | The id of the urb-it pickup location (one of the customer shops). Provided by urb-it |
| Consumer key                               | A key for the urb-it api consumer user. Provided by urb-it                           |
| Consumer secret                            | A secret key for the urb-it api consumer user. Provided by urb-it                    |
| OAuth token                                | The token for api authentication. Provided by urb-it.                                |
| Handling fee                               | Fee for using urb-it, added to the total shipping cost.                              |
| Maximum Package Weight [Kg]                | If the store sells heavy items that cant be transported with urb-it set this to the maximum value allowed, if not, 0 is sufficient as it means unlimited. |
| Displayed error message                    | Show a custom error message, If empty error messages will be returned directly from the urb-it api |
| Only specific products available for urbit | If only specific products should be able to be delivered with urb-it. If yes, it will look on a specific setting on each individual product. |
| Ship to applicable countries               | If set to ’All’ the shipping method will only be available to the countries selected in the list ”General” > ”Countries Options” > ”Allow Countries”. |
| Ship to Specific countries                 | If you don’t want to allow this payment method to be visible for all countries & you have set ’Allowed countries’ to ’Specific countries’, this list will determine which countries the shipping method should be available for. |
| Sorting                                    | This determines in which order the shipping method will be displayed in. Lowest number is displayed first |

##Manual installation##

If receiving the module in a compressed file.Unpack the files into the magento installation directory.
Please be aware that some filesystems replaces the directories and can destroy current directories. Take caution.

When done, files should be in:

* app/code/local/vaimo/urbit
* etc/modules
* design/frontend/base/default
* skin/frontend/base/default/css

If working at VaimoInstall it through aja/composer or with appropriate iec-command.

##Customisation##

###Templates###

Some of the module templates can be customised to be as the shop owner wants.
The files are located under design/frontend/base/template/vaimo/urbit.
Copy the vaimo/urbit files over to your own customised theme with the same structure from the template directory and forward.
Change the files to look as you want them to. Clear cache and reload the page.

Some of the customisable files are:

* available.phtml - Overrides the available options template in order to handle urbit module
* deliverydetails.phtml - Visible in the checkout on the shipping option.
* info.phtml - Visible on the product page.
* success.html - Visible on the success page if order was successful.
* success.html - Visible on the success page if order was successful.
    
###CMS block###

There is one page ("/urb-it") connected to urb-it module and that is created the first time you install the urb-it module. 
It contains a static CMS block "Urb-it - Text Block", and a category, if you wish to have that in your menu you can activate 
that category to be "visible in menu" on the category itself.


##Troubleshooting##

If shipping option is not visible at all in the checkout.

* Check if country selected is “Sweden”.
* Check if shipping module is enabled and has details entered in configuration.
* If custom error message appears under the urb-it shipping option in the checkout This means that the api has reported an error.
* Usually it is because of something wrong in the settings or maybe just that the postal code is outside the delivery area for urb-it.
* Remove the custom error message in admin and try again. You will now see a correct error message.