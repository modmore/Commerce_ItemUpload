Commerce ItemUpload Module

A Commerce module that allows customers to upload files that are attached to order items and included in order emails.

Installation:
1. Go to Commerce > Modules in the MODX manager
2. Enable the "Item Upload" module and configure the module settings
3. For products that need it, change the add to cart form to have `enctype="multipart/form-data"` and an `<input type="file" name="upload" />` (where the name matches your configuration).

For example, here's what the add to cart form might look like:

```
<form method="post" enctype="multipart/form-data" action="[[~[[++commerce.cart_resource]]]]">
    <input type="hidden" name="add_to_cart" value="1" />
    <input type="hidden" name="product" value="[[*id]]" />
    <input type="file" name="upload" />
    <button type="submit">Add to Cart</button>
</form>
```
