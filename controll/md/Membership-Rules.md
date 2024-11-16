# Membership Rules

Membership rules are the method ConTroll uses to limit what memberships are shown to a person, beyond the date ranges and the atcon and
online flags in the memList entry.  it is currently used in onlineReg, portal, atcon, and registration

The rules are stored in two tables:
* memRules - the rule, and to which memList entries it applies
* memRuleItems - the steps a rule must pass to consider allowing the memberships it applies to to be purchased

Membership rules are configured in the Registration Admin section of ConTroll and executed any time the system needs to display a set of
memberships to purchase.  The configuration page includes a simulator that emulates the rules for a cart containing only a single person.
It currently allos for a simulated date, but only applies to online entries.  It need to be extended to support atcon and
mail in registration(all entries)

## How the rules system chooses memList entries to display:

Each process that uses membership rules loads the memList table into an associative array with process enforcing the date range limitation
and the atcon or online flags.  Once the array is built, the rest is up to the rules javascript. NOTE: at present rules are only available
in the browser as javascript, and not in the server as PHP.

A membership is allowed to be shown unless it fails any rule. (every rule is considered as a Boolean AND, so any denial denies the entry)

Membership Rules loops over all memList entries in the array:
* All memberships are checked against the existing cart.
* It first applies some implicit rules unless the skipImplicit flag is set.  (skipImplicit is set to false by default, and true when
validating if a memList entry can be removed or deleted from the cart such that the remaining items in the cart are still valid.
  * Example: if an attending requires a club, then removing club while there is an attending in the cart should not be allowed.)

### Implicit Rules: (apply to the memList entry and the cart for the current person)

1. If the memList memType is full and the memList memCategory is not upgrade this implicit rule is applied.
    1. If the cart already has a full membership in it, this memList entry is denied. 
    2. This check is enforced independently for the current convention year and for any year-ahead memberships.

2. One day and full are mutually exclusive. 
   1. If the memList memtype is full this implicit rule is applied
      1. If the cart alread has a one day membership, this memList entry is denied.
   2. If the memList memtype is oneday this implicit rule is applied
      1. If the cart alread has a full membership, this memList entry is denied.

3. if the cart has a full, memType virtual is not allowed
   1. If the cart already has a full membership, this memList entry is denied.

4. Enforce the Only One flag in the memCategory entry
   1. if the memCategory entry has OnlyOne set to 'Y', and there already is an item in the cart with this memCategory, this memList entry is denied.

### Explicit Rules;  The system then loops over all of the rules.
First a rule is checked based on the criteria for matching in the memRules table. (a Boolean AND across all of the matching criteria)
1. If rule memType list is not empty and the memList memType is not in the rule memType list, this rule is skipped.
2. If rule memCatgory list is not empty and the memList memCategory is not in the rule memCategory list, this rule is skipped.
3. If rule memAge list is not empty and the memList memAge is not in the rule memAge list, this rule is skipped.
4. If rule memId list is not empty and the memList memId is not in the rule memId list, this rule is skipped.

 If the rule matches, the rule is processed

#### Rule Steps

To process a rule, each item for the rule entry (step) is evaluated in order.
 Each step must pass or the memList entry is denied. (Boolean AND of the steps)

For each step:
* if the rules are being checked for a remove/delete type operation, and this step number is 999 and the step type is
Need Any/Not Any this step is skipped (considered passed, as this type of step is designed to prevent you adding an item that
matches an item already in the cart. Removing/deleting this entry should not be prevented because it is in the cart.)
* A step can apply to only those elements in the cart (account) belonging to this person or to the entire cart (account)
 for all people (called all in account, as in the portal it's the account, in atcon/registration its the cart). For the
 purposes of this section, it will be called the 'cart', for either person or all.)
* If a step passes, the memList entry being checked is allowed.  If the step fails, the memList entry is not allowed and no further steps are checked.

#### Step Types
Steps can be of several types:
* Need Any: at least one item in the cart must match the matching criteria
* Need All: all items in the cart must match the matching criteria
* Not One: one of the items in the cart must not match the matching criteral
* Not Any: all of the items in the cart must not match the matching criteria
* Limit Age: Need Any, but enforces that there must be an Age Requirement in the matching criteria

Just like the memRule itself, the step uses the same matching criteria.

##### For need any/not any/Limit Age:
1. If step memType list is not empty and the cart does not have an entry with a memType in the step memType list,
this step is considered passed for not any, or failed for need any.
2. If step memCatgory list is not empty and the cart does not have an entry with a memCategory in the step memCategory list,
 this step is considered passed for not any, or failed for need any.
3. If step memAge list is not empty and the cart does not have an entry with a memåAge in the step memAge list,
this step is considered passed for not any, or failed for need any.
4. If step memId list is not empty and the cart does not have an entry with a memId in the step memId list,
this step is considered passed for not any, or failed for need any.
##### For need all/not all:
1. If step memType list is not empty and the cart does not have any entries with a memType in the step memType list,
2. If step memCatgory list is not empty and the cart does not have any entries with a memCategory in the step memCategory list,
this step is considered passed for not all, or failed for need all.
3. If step memAge list is not empty and the cart does not have any entries with a memåAge in the step memAge list,
this step is considered passed for not all, or failed for need all.
4. If step memId list is not empty and the cart does not have any entries with a memId in the step memId list,
this step is considered passed for not all, or failed for need all.
