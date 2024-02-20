<tr>
    <td class="align_top icon"><img alt="" class="closer" src="images/controls/arrow_closed.png"
                                    onclick="if (this.open) { document.getElementById('{ID}').className = 'entry_closed'; this.src='images/controls/arrow_closed.png'; this.open = false; } else { document.getElementById('{ID}').className = 'entry_open'; this.src='images/controls/arrow_open.png'; this.open = true; }">
    </td>
    <td class="align_top entry">{TITLE}
        <div id="{ID}" class="entry_closed">{MESSAGE}</div>
    </td>
</tr>