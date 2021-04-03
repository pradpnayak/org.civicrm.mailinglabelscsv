{include file="CRM/Contact/Form/Task/Label.tpl"}

<table class="mailingCSV" style="Display:none;">
  <tr class="crm-contact-task-mailing-label-form-block-do_not_trade">
    <td></td> <td>{$form.do_not_trade.html} {$form.do_not_trade.label}</td>
  </tr>
</table>

{literal}
<script type="text/javascript">
  CRM.$(function($) {
    $('tr.crm-contact-task-mailing-label-form-block-label_name')
      .remove();
    $('.mailingCSV tr.crm-contact-task-mailing-label-form-block-do_not_trade')
      .insertAfter('tr.crm-contact-task-mailing-label-form-block-do_not_mail');
  });
</script>
{/literal}
