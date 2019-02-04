How it works
============

AdSelect collects the following information:

- Campaigns (start and end time, filters)
- Banners (banner sizes, keywords, publisher)
- Payments for events (keywords, paid amount)

"Value" of banners is determined based on keywords. Each keyword has an average payment value determined by past payments.

.. note::

    [var] in the following documentation means a configurable variable. It can be different for each step.

Selection process
-----------------

0. AdSelect recieves a banner request.
#. Get best banners for keywords.
#. Get banners with low impressions.
#. Mix both banner pools and return a banner from the mixed pool.

In detail
---------

#. Get best banners for keywords

   #. Get all banners for given publisher and banner size.
   #. Return [var] banners for each keyword.
   #. Sort banners by highest paid keywords.
   #. Return banners.

#. Get banners with low impressions.

   #. Get [var] banners from all banners with the proper size (random sample or all of them).
   #. From step 1, choose banners with [var] impressions or less.
   #. From step 2, return [var] banners.

#. Mix both banner pools and return a banner from the mixed pool.

   #. Add low-impression banners to best paid banners.
   #. Shuffle the banner pool.
   #. Return [var] banners.
