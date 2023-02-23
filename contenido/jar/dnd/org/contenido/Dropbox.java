package org.contenido;

import java.awt.Color;

import javax.swing.JLabel;


/**
 * Dropbox is the label that files can be dropped on
 *
 *
 * @author Mischa Holz
 * @version 1.1, 01/13/2012
 * @since 1.0
 */

public class Dropbox extends JLabel {
	private static final long serialVersionUID = 1L;

	/**
	 * Creates the label with fixed size and text
	 */
	public Dropbox() {
		this.setText("Drop files here");
		this.setBounds(DropboxMain.dropAreaX, DropboxMain.dropAreaY, DropboxMain.dropAreaWidth, DropboxMain.dropAreaHeight);
		this.setOpaque(true);
		this.setBackground(Color.white);
		this.setHorizontalAlignment(CENTER);
		this.setHorizontalTextPosition(CENTER);
	}
}
