# magento2-similarSkuUpdate
Update product variations that has similar sku ranges

Some stores run products that are similar. Basically variations on one products (example electornics parts)

The SKUs of these products are 'similar' as in they have a variation # sequence, but teh stock is set for all of them to be teh same, so if one is sold, stock for all must be decreased.

This code will scan for these similar products and set the stock levels accordingly.

```
[2021-05-29 05:19:46] main.NOTICE: 5 Similar Skus detected to 942041-001-V7G [] []
[2021-05-29 05:19:46] main.NOTICE: Updating 942041-001 stock value to 0 [] []
[2021-05-29 05:19:46] main.NOTICE: Updating 942041-001-4PT stock value to 0 [] []
[2021-05-29 05:19:46] main.NOTICE: Updating 942041-001-LR2 stock value to 0 [] []
[2021-05-29 05:19:46] main.NOTICE: Updating 942041-001-T24 stock value to 0 [] []
[2021-05-29 05:19:46] main.NOTICE: Updating 942041-001-Y4C stock value to 0 [] []
```

This is a module created to a clients specific needs, but may be of use to other as well.


